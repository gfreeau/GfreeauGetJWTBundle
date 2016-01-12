<?php

namespace Gfreeau\Bundle\GetJWTBundle\Security\Firewall;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use InvalidArgumentException;

class GetJWTListener implements ListenerInterface
{
    protected $providerKey;
    protected $options;
    protected $logger;

    private $securityContext;
    private $authenticationManager;
    private $successHandler;
    private $failureHandler;

    /**
     * @param TokenStorageInterface $securityContext
     * @param AuthenticationManagerInterface $authenticationManager
     * @param $providerKey
     * @param AuthenticationSuccessHandlerInterface $successHandler
     * @param AuthenticationFailureHandlerInterface $failureHandler
     * @param array $options
     * @param LoggerInterface $logger
     * @throws InvalidArgumentException
     */
    public function __construct(TokenStorageInterface $securityContext, AuthenticationManagerInterface $authenticationManager, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler = null, array $options = array(), LoggerInterface $logger = null)
    {
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->options = array_merge(array(
            'username_parameter' => 'username',
            'password_parameter' => 'password',
            'post_only' => true,
        ), $options);
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->options['post_only'] && !$request->isMethod('POST')) {
            $event->setResponse(new JsonResponse('invalid method', 405));
            return;
        }

        if ($this->options['post_only']) {
            $username = trim($request->request->get($this->options['username_parameter'], null, true));
            $password = $request->request->get($this->options['password_parameter'], null, true);
        } else {
            $username = trim($request->get($this->options['username_parameter'], null, true));
            $password = $request->get($this->options['password_parameter'], null, true);
        }

        try {
            $token = $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
            $this->securityContext->setToken($token);
            $response = $this->onSuccess($event, $request, $token);

        } catch (AuthenticationException $e) {
            if (null == $this->failureHandler) {
                throw $e;
            }

            $response = $this->onFailure($event, $request, $e);
        }

        $event->setResponse($response);
    }

    protected function onSuccess(GetResponseEvent $event, Request $request, TokenInterface $token)
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('User "%s" has retrieved a JWT', $token->getUsername()));
        }

        $response = $this->successHandler->onAuthenticationSuccess($request, $token);

        if (!$response instanceof Response) {
            throw new \RuntimeException('Authentication Success Handler did not return a Response.');
        }

        return $response;
    }

    protected function onFailure(GetResponseEvent $event, Request $request, AuthenticationException $failed)
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('JWT request failed: %s', $failed->getMessage()));
        }

        $response = $this->failureHandler->onAuthenticationFailure($request, $failed);

        if (!$response instanceof Response) {
            throw new \RuntimeException('Authentication Failure Handler did not return a Response.');
        }

        return $response;
    }
}