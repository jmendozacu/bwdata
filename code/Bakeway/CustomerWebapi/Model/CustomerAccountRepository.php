<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomerWebapi
 * @author    Bakeway
 */

namespace Bakeway\CustomerWebapi\Model;

use Bakeway\CustomerWebapi\Api\CustomerAccountRepositoryInterface as CustomerAccountInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\Data\Customer as CustomerDataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Api\CustomerTokenServiceInterface as CustomerTokenService;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;

class CustomerAccountRepository implements CustomerAccountInterface
{

    const FACEBOOK_PLATFORM = "fb";
    const GPLUS_PLATFORM = "google";

    const FACEBOOK_API_URL = "https://graph.facebook.com/me?access_token=";
    const GOOGLE_API_URL = "https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=";

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerDataObject
     */
    protected $customerDataObject;

    /**
     * @var CustomerModel
     */
    protected $customerModel;

    /**
     * @var TokenModelFactory
     */
    protected $tokenFactory;

    /**
     * @var CustomerTokenService
     */
    protected $tokenService;

    /**
     * @var TokenCollectionFactory
     */
    protected $tokenCollectionFactory;

    /**
     * CustomerAccountRepository constructor.
     * @param CustomerRepository $customerRepository
     * @param CustomerDataObject $customerDataObject
     * @param CustomerModel $customerModel
     * @param TokenModelFactory $tokenFactory
     * @param CustomerTokenService $tokenService
     * @param TokenCollectionFactory $tokenCollectionFactory
     */
    public function __construct(
        CustomerRepository $customerRepository,
        CustomerDataObject $customerDataObject,
        CustomerModel $customerModel,
        TokenModelFactory $tokenFactory,
        CustomerTokenService $tokenService,
        TokenCollectionFactory $tokenCollectionFactory
    )
    {
        $this->customerRepository = $customerRepository;
        $this->customerDataObject = $customerDataObject;
        $this->customerModel = $customerModel;
        $this->tokenFactory = $tokenFactory;
        $this->tokenService = $tokenService;
        $this->tokenCollectionFactory = $tokenCollectionFactory;
    }

    /**
     * @api
     * @param mixed $data
     * @return mixed
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function socialLogin($data)
    {
        if (isset($data['email'], $data['socialid'], $data['socialaccesstoken'], $data['socialplatform'])) {
            $socialId = $data['socialid'];
            $socialAccessToken = $data['socialaccesstoken'];
            $email = $data['email'];

            $verification = false;
            if ($data['socialplatform'] == self::FACEBOOK_PLATFORM) {
                $verification = $this->verifyFacebookUser($socialId, $socialAccessToken);
            } elseif ($data['socialplatform'] == self::GPLUS_PLATFORM) {
                $verification = $this->verifyGoogleUser($socialId, $socialAccessToken);
            } else {
                throw new InputException(__('Requested social platform not available.'));
            }

            if ($verification === true) {
                /**
                 * check if customer already exist in bakeway system
                 */
                $customerObj = $this->customerModel;
                $customerObj->setWebsiteId(1);
                $customerObj->loadByEmail($email);
                if ($customerObj->getId()) {
                    $this->tokenService->revokeCustomerAccessToken($customerObj->getId());
                    return $this->tokenFactory->create()->createCustomerToken($customerObj->getId())->getToken();
                } else {
                    $customer = $this->customerDataObject;
                    if (isset($data['name'])) {
                        $nameArr = explode(" ", $data['name']);
                        if (isset($nameArr[0])) {
                            $customer->setFirstname($nameArr[0]);
                        }
                        if (isset($nameArr[1])) {
                            $customer->setLastname($nameArr[1]);
                        }
                    }
                    if (isset($data['dob'])) {
                        $customer->setDob($data['dob']);
                    }
                    if (isset($data['profile_pic'])) {
                        $customer->setCustomAttribute('social_profile_pic', $data['profile_pic']);
                        $customer->setCustomAttribute('social_profile_pic_platform', $data['socialplatform']);
                    }
                    if (isset($data['mobile_number'])) {
                        $customer->setCustomAttribute('mobile_number', $data['mobile_number']);
                    }
                    if ($data['socialplatform'] == self::FACEBOOK_PLATFORM) {
                        $customer->setCustomAttribute('fb_user_id', $socialId);
                    } elseif ($data['socialplatform'] == self::GPLUS_PLATFORM) {
                        $customer->setCustomAttribute('gplus_user_id', $socialId);
                    }
                    $customer->setCustomAttribute('is_password_editable', '0');

                    $customer->setEmail($email);
                    $newCustomer = $this->customerRepository->save($customer);
                    return $this->tokenFactory->create()->createCustomerToken($newCustomer->getId())->getToken();
                }
            } else {
                throw new NoSuchEntityException(__('User social profile is not valid.'));
            }
        } else {
            throw new InputException(__('One of the required parameter is missing.'));
        }
    }

    /**
     * @param $socialId
     * @param $socialAccessToken
     * @return bool
     * @throws LocalizedException
     */
    public function verifyFacebookUser($socialId, $socialAccessToken)
    {
        $userDetails = self::FACEBOOK_API_URL .$socialAccessToken;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $userDetails);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $userData = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($userData);

        if (isset($response->id) && $response->id == $socialId) {
            return true;
        }

        return false;
    }

    /**
     * @param $socialId
     * @param $socialAccessToken
     * @return bool
     */
    public function verifyGoogleUser($socialId, $socialAccessToken)
    {
        $userDetails = self::GOOGLE_API_URL .$socialAccessToken;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $userDetails);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $userData = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($userData);

        if (isset($response->user_id) && $response->user_id == $socialId) {
            return true;
        }

        return false;
    }

    /**
     * @api
     * @param string $token
     * @return bool
     * @throws LocalizedException
     */
    public function logout($token) {
        if (strpos($token, ":") !== false) {
            $tokenArr = explode(":", $token);
            if (!isset($tokenArr[1])) {
                return true;
            } else {
                $token = $tokenArr[1];
            }
        }

        $tokenCollection = $this->tokenCollectionFactory->create()
            ->addFieldToFilter('token', $token);
        try {
            foreach ($tokenCollection as $token) {
                $token->delete();
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}