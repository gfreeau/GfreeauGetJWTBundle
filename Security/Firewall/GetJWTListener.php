<?php

namespace Gfreeau\Bundle\GetJWTBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Class GetJWTListener
 *
 * @package Gfreeau\Bundle\GetJWTBundle\Security\Firewall
 */
class GetJWTListener implements ListenerInterface
{
    /**
     * @type
     */
    protected $providerKey;

    /**
     * @type array
     */
    protected $options;
    
    /**
     * @type null|LoggerInterface
     */
    protected $logger;

    /**
     * @type TokenStorageInterface
     */
    private $securityContext;

    /**
     * @type AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @type AuthenticationSuccessHandlerInterface
     */
    private $successHandler;

    /**
     * @type null|AuthenticationFailureHandlerInterface
     */
    private $failureHandler;

    /**
     * GetJWTListener constructor.
     *
     * @param TokenStorageInterface                      $securityContext
     * @param AuthenticationManagerInterface             $authenticationManager
     * @param                                            $providerKey
     * @param AuthenticationSuccessHandlerInterface      $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     * @param array                                      $options
     * @param LoggerInterface|null                       $logger
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
        ), $options);
        $this->logger = $logger;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->isMethod('POST')) {
            if ('json' === $request->getContentType()) {
                $params = json_decode($request->getContent(), true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException('Bad JSON request.');
                }
                $parameterBag = new ParameterBag($params);
            } else {
                $parameterBag = $request->request;
            }
        } else {
            $parameterBag = $request->query;
        }

        $username = trim($parameterBag->get($this->options['username_parameter']));
        $password = $parameterBag->get($this->options['password_parameter']);

        try {
            $token = $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
            $this->securityContext->setToken($token);
            $response = $this->onSuccess($event, $request, $token);

        } catch (AuthenticationException $e) {
            if (null === $this->failureHandler) {
                throw $e;
            }

            $response = $this->onFailure($event, $request, $e);
        }

        $event->setResponse($response);
    }

    /**
     * @param GetResponseEvent $event
     * @param Request          $request
     * @param TokenInterface   $token
     *
     * @return Response
     */
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

    /**
     * @param GetResponseEvent        $event
     * @param Request                 $request
     * @param AuthenticationException $failed
     *
     * @return Response
     */
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
