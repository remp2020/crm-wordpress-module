# CRM Wordpress Module

[![Translation status @ Weblate](https://hosted.weblate.org/widgets/remp-crm/-/wordpress-module/svg-badge.svg)](https://hosted.weblate.org/projects/remp-crm/wordpress-module/)

## User authentication

CRM supports user authentication against configured Wordpress instance. To enable this feature,

### Installation

1. Install our [Wordpress plugin](https://github.com/remp2020/dn-remp-wp-auth) exposing API to validate credentials.

2. Configure Wordpress CMS URL and Wordpress auth token in CRM admin settings (`/admin/config-admin` - Integrations)

3. Register the authenticator in your own module:

```php
class DemoModule extends \Crm\ApplicationModule\CrmModule
{
    // ...
    public function registerAuthenticators(\Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface $authenticatorManager)
    {
        $authenticatorManager->registerAuthenticator(
            $this->getInstance(\Crm\WordpressModule\Authenticator\WordpressAuthenticator::class),
            100
        );
    }
// ...
}
```

Once enabled, every time user tries to log in, following will happen:

* First CRM will use the default set of authenticators with higher priority. Among others, it tries to log user in against local `users` table.
* If all authenticators with higher priority fail, CRM tries to use `WordpressAuthenticator` and validates the credentials against Wordpress instance you configured.
* If it's successful, CRM will create the user locally and set the same password that user just validated against Wordpress, so CRM is able to validate the password itself in the future.

We recommend to have this authenticator enabled only for some transition period (couple of months). When disabled, users who didn't authenticate within the transition period will have to create the account / reset the password (depending on whether you migrated the users before or not).

### Configuration

When `WordpressAuthenticator` creates new user in CRM, it sets the password validated in Wordpress so people can log in to CRM with the same password.

However if the account already exists in CRM and it has different password, `WordpressAuthenticator` **doesn't change password** of the user by default. If you want to set validated Wordpress passwords even for existing CRM accounts, please add following snippet to your `app/config/config.neon`.

```neon
# enable WordressModule extension
extensions:
    wordpress: Crm\WordpressModule\DI\WordpressModuleExtension

wordpress:
    # configure authenticator
    authenticator:
        passwordReset: true
```

## Security and user migration

By default, it's not a good idea to have two user bases at the same time and we recommend the CRM to be your primary source of truth. That way you can prevent account hijacking and other vulnerabilities that come with multiple authentication mechanisms. We've thought of two scenarios that could help you with the migration.

### Migrate first, authenticate later

To prevent any kind of ambiguity, both Wordpress and CRM users should be connected. The best way to achieve this is to migrate/synchronize WP users to CRM first - see [sync user API endpoint]() below.

If you don't expect users to come from any other source, you could make hard link via `users.ext_id` column. `WordpressModule` can handle this for you, just configure the following flag in your `app/config/config.neon`. Otherwise there will only be soft link between `wordpress_users` table and `users` table.

```neon
# enable WordressModule extension
extensions:
    wordpress: Crm\WordpressModule\DI\WordpressModuleExtension

wordpress:
    # enable ext ID referencing
    extIdReferencing: true
```

## API documentation

All examples use `http://crm.press` as a base domain. Please change the host to the one you use
before executing the examples.

All examples use `XXX` as a default value for authorization token, please replace it with the
real tokens:

* *API tokens.* Standard API keys for server-server communication. It identifies the calling application as a whole.
They can be generated in CRM Admin (`/api/api-tokens-admin/`) and each API key has to be whitelisted to access
specific API endpoints. By default the API key has access to no endpoint. 

API responses can contain following HTTP codes:

| Value | Description |
| --- | --- |
| 200 OK | Successful response, default value | 
| 400 Bad Request | Invalid request (missing required parameters) | 
| 403 Forbidden | The authorization failed (provided token was not valid) | 
| 404 Not found | Referenced resource wasn't found | 
| 409 Conflict | Requested resource is in conflict with current server state | 

If possible, the response includes `application/json` encoded payload with message explaining
the error further.

---

#### GET `/api/v1/wordpress/sync-user`

API call creates/updates user in CRM based on the information provided from Wordpress. Scenarios are handled as follows:

* If user doesn't exist in CRM, it's created and linked to the Wordpress user.
* If user already exists in CRM, and the provided Wordpress ID matches with the one linked in CRM, user is updated.
* If user already exists in CRM and the provided Wordpress ID doesn't match or it isn't set on CRM user, API returns HTTP 409 conflict.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Payload*

```json5
{
    "wordpress_id": 123, // required; ID of user in Wordpress.
    "email": "admin@example.com", // required; Email of user in Wordpress.
    "registered_at": "2020-03-13T14:02:44+00:00", // required; RFC3339-formatted time of user registration in Wordpress.
    "user_login": "admin", // required; Login of user in Wordpress .
    "user_nicename": "admin", // optional; Nicename of user in Wordpress.
    "user_url": "http://www.example.com", // optional; User's URL in Wordpress .
    "display_name": "Example Admin", // optional; Display name of user in Wordpress.
    "first_name": "Example", // optional; First name of user in Wordpress
    "last_name": "Admin" // optional; Last name of user in Wordpress
}
```

##### *Example:*

```shell
curl -request POST 'http://crm.press/api/v1/wordpress/sync-user' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer XXX' \
--data-raw '{
    "wordpress_id": 145,
    "email": "admin@example.fu",
    "registered_at": "2020-03-13T14:02:44+00:00",
    "user_login": "admin",
    "user_nicename": "admin",
    "user_url": "http://www.example.com",
    "display_name": "Example Admin",
    "first_name": "Example", 
    "last_name": "vcvc"
}'
```

Response:

```json5
{
    "user_id": 374513,
    "wordpress_id": 145,
    "email": "admin@example.fu",
    "login": "admin",
    "registered_at": "2020-03-13T14:02:44+01:00",
    "nicename": "admin",
    "url": "http://www.example.com",
    "display_name": "Example Admin",
    "first_name": "Example",
    "last_name": "vcvc"
}
```


