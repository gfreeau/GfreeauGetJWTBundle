<?php

namespace Gfreeau\Bundle\GetJWTBundle\Security\Firewall;

use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use RuntimeException;
use InvalidArgumentException;

class GetJWTListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;
    protected $providerKey;
    protected $options;
    protected $dispatcher;
    protected $encoder;

    /**
     * @param SecurityContextInterface $securityContext
     * @param AuthenticationManagerInterface $authenticationManager
     * @param $providerKey
     * @param array $options
     * @param EventDispatcherInterface $dispatcher
     * @param JWTEncoder $encoder
     * @throws InvalidArgumentException
     */
    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, $providerKey, array $options = array(), EventDispatcherInterface $dispatcher = null, JWTEncoder $encoder = null)
    {
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->options = array_merge(array(
            'username_parameter' => 'username',
            'password_parameter' => 'password',
            'ttl' => 86400,
            'post_only' => true,
        ), $options);
        $this->dispatcher = $dispatcher;
        $this->encoder = $encoder;
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
            $response = $this->onAuthenticationSuccess($token);
        } catch (AuthenticationException $e) {
            $response = $this->onAuthenticationFailure();
        }

        $event->setResponse($response);
    }

    /**
     * @param TokenInterface $token
     * @return Response
     * @throws RuntimeException
     */
    protected function onAuthenticationSuccess(TokenInterface $token)
    {
        if (!$this->encoder) {
            throw new RuntimeException('encoder must be an instance of JWTEncoder to create tokens');
        }

        $user = $token->getUser();

        $payload             = array();
        $payload['exp']      = time() + $this->options['ttl'];
        $payload['username'] = $user->getUsername();

        $jwt = $this->encoder->encode($payload)->getTokenString();

        $response = $jwt;

        if ($this->dispatcher) {
            $event = new AuthenticationSuccessEvent(array('token' => $jwt), $user);
            $this->dispatcher->dispatch(Events::AUTHENTICATION_SUCCESS, $event);
            $response = $event->getData();
        }

        return new JsonResponse($response);
    }

    /**
     * @return JsonResponse
     */
    protected function onAuthenticationFailure()
    {
        return new JsonResponse('invalid credentials', 401);
    }
}