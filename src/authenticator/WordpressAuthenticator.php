<?php

namespace Crm\WordpressModule\Authenticator;

use Crm\ApplicationModule\Authenticator\AuthenticatorInterface;
use Crm\UsersModule\Auth\UserAuthenticator;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Events\UserSignInEvent;
use Crm\UsersModule\Repository\UserAlreadyExistsException;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\WordpressModule\Events\WordpressUserMatchedEvent;
use Crm\WordpressModule\Model\ApiClient;
use League\Event\Emitter;
use Nette\Database\Table\IRow;
use Nette\Localization\ITranslator;
use Nette\Security\AuthenticationException;

class WordpressAuthenticator implements AuthenticatorInterface
{
    protected $source = UserSignInEvent::SOURCE_WEB;

    private $email;

    private $password;

    private $passwordReset = false;

    private $extIdReferencing = false;

    private $usersRepository;

    private $apiClient;

    private $userManager;

    private $translator;

    private $emitter;

    public function __construct(
        UsersRepository $usersRepository,
        ApiClient $apiClient,
        UserManager $userManager,
        ITranslator $translator,
        Emitter $emitter
    ) {
        $this->usersRepository = $usersRepository;
        $this->apiClient = $apiClient;
        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->emitter = $emitter;
    }

    public function authenticate()
    {
        if ($this->email !== null && $this->password !== null) {
            return $this->process();
        }

        return false;
    }

    public function setCredentials(array $credentials): AuthenticatorInterface
    {
        $this->email = $credentials['username'] ?? null;
        $this->password = $credentials['password'] ?? null;
        if (isset($credentials['source'])) {
            $this->source = $credentials['source'];
        }

        return $this;
    }

    public function getSource() : string
    {
        return $this->source;
    }

    public function shouldRegenerateToken(): bool
    {
        return false;
    }

    private function process(): IRow
    {
        $wpUser = $this->apiClient->credentialsAuthenticate($this->email, $this->password);
        if (!$wpUser) {
            throw new AuthenticationException($this->translator->translate('users.authenticator.invalid_credentials'), UserAuthenticator::INVALID_CREDENTIAL);
        }

        $user = $this->usersRepository->getByExternalId($wpUser->ID);
        if (!$user) {
            try {
                $user = $this->userManager->addNewUser($wpUser->data->user_email, false, 'wordpress', null, false);
            } catch (UserAlreadyExistsException $e) {
                $user = $this->userManager->loadUserByEmail($wpUser->data->user_email);
                $matched = false;
                foreach ($user->related('wordpress_users') as $wordpressUser) {
                    if ($wordpressUser->wordpress_id === $wpUser->ID) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    throw new AuthenticationException($this->translator->translate('wordpress.authenticator.invalid_credentials'), UserAuthenticator::INVALID_CREDENTIAL);
                }
                $this->emitter->emit(new WordpressUserMatchedEvent($user, $wpUser));
            }

            if ($this->passwordReset) {
                $this->userManager->resetPassword($user->email, $this->password, false);
                $user = $this->userManager->loadUserByEmail($user->email); // refresh user to have fresh password
            }

            if ($this->extIdReferencing) {
                $this->usersRepository->update($user, [
                    'ext_id' => $wpUser->ID,
                    'first_name' => $wpUser->data->first_name,
                    'last_name' => $wpUser->data->last_name,
                ]);
            }
        } else {
            if ($this->passwordReset) {
                $this->userManager->resetPassword($user->email, $this->password, false);
                $user = $this->userManager->loadUserByEmail($user->email); // refresh user to have fresh password
            }
        }

        $this->usersRepository->addSignIn($user);
        return $user;
    }

    public function setPasswordReset($flag = true)
    {
        $this->passwordReset = $flag;
    }

    public function setExtIdReferencing($flag = true)
    {
        $this->extIdReferencing = $flag;
    }
}
