GfreeauGetJWTBundle
===================

This bundle requires LexikJWTAuthenticationBundle. Please read the docs for that bundle at https://github.com/lexik/LexikJWTAuthenticationBundle

It provides a replacement for the form factory "form_login". "form_login" is designed for use with cookies and will set cookies even when the stateless parameter is true.


Installation
------------

Details for composer coming soon

Usage
-----

#### Example of possible `security.yml` :

``` yaml
    firewalls:
        gettoken:
            pattern:  ^/api/getToken$
            stateless: true
            gfreeau_get_jwt:
                post_only: true

        # protected firewall, where a user will be authenticated by its jwt token
        api:
            pattern:   ^/api
            stateless: true
            # default configuration
            lexik_jwt: ~ # check token in Authorization Header, with a value prefix of e:    bearer

```

A route must be defined for the url you wish to get your token:

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
