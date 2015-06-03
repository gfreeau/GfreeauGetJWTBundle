GfreeauGetJWTBundle
===================

This bundle requires LexikJWTAuthenticationBundle. Please read the docs for that bundle at https://github.com/lexik/LexikJWTAuthenticationBundle

It provides a replacement for the security factory "form_login". "form_login" is designed for use with cookies and will set cookies even when the stateless parameter is true.

The 'switch_user' and 'logout' config options are not supported with this security factory as they rely on cookies.

Authenticating json web tokens is provided by LexikJWTAuthenticationBundle.

Json Web Tokens are perfect for use in SPA such as AngularJS or in mobile applications. Using this bundle you can easily use symfony2 for your API.

You should use SSL connections only for your API to protect the contents of your json web tokens.

Installation
------------

Installation with composer:

``` json
"require": {
    "gfreeau/get-jwt-bundle": "~1.0"
}
```

Next, be sure to enable the bundle in your `app/AppKernel.php` file:

``` php
public function registerBundles()
{
    return array(
        // ...
        new Gfreeau\Bundle\GetJWTBundle\GfreeauGetJWTBundle(),
        // ...
    );
}
```

Usage
-----

#### Example of possible `security.yml` :

``` yaml
    firewalls:
        gettoken:
            pattern:  ^/api/getToken$
            stateless: true
            gfreeau_get_jwt:
                # this is the default config
                username_parameter: username
                password_parameter: password
                post_only: true
                authentication_provider: security.authentication.provider.dao
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        # protected firewall, where a user will be authenticated by its jwt token
        api:
            pattern:   ^/api
            stateless: true
            # default configuration
            lexik_jwt: ~ # check token in Authorization Header, with a value prefix of e:    bearer

```

This bundle supports the AuthenticationSuccessEvent from LexikJWTAuthenticationBundle, read their documentation for more information. You can use this event to append more information to your json web token.

A route must be defined for the url you wish to use to get your token:

```php
/**
 * @Route("/api/getToken")
 */
public function getTokenAction()
{
    // The security layer will intercept this request
    return new Response('', 401);
}
```
