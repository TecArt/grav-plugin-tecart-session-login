<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Plugin\Grav\Common\User\User;
use Grav\Plugin\Login\Events\UserLoginEvent;

class TecartSessionLoginPlugin extends Plugin
{
    /**
     * This way we can use that event (which is the first event available to plugins) to determine if we should subscribe to other events.
     * @return array
     */
    public static function getSubscribedEvents(){
        return [
            'onPluginsInitialized' => [
                ['onPluginsInitialized', 10]
            ],
            'onUserLoginAuthenticate'   => ['userLoginAuthenticate', 1000],
            'onUserLoginFailure'        => ['userLoginFailure', 0],
            'onUserLogin'               => ['userLogin', 0],
            'onUserLogout'              => ['userLogout', 0],
            'onAdminTwigTemplatePaths'  => ['onAdminTwigTemplatePaths', 0]
        ];
    }

    /**
     * This is the first plugin event available. At this point the following objects have been initiated:
     * - Uri
     * - Config
     * - Debugger
     * - Cache
     * - Plugins
     * Determine if the plugin should run
     *
     * @return void
     */
    public function onPluginsInitialized() : void
    {

        // Check to ensure admin plugin is enabled.
        if (!$this->grav['config']->get('plugins.admin.enabled')) {
            throw new \RuntimeException('The Admin plugin needs to be installed and enabled');
        }
        // Check to ensure login plugin is enabled.
        if (!$this->grav['config']->get('plugins.login.enabled')) {
            throw new \RuntimeException('The Login plugin needs to be installed and enabled');
        }
    }

    /**
     * Add twig paths to plugin templates.
     *
     * @param $event
     * @return void
     */
    public function onAdminTwigTemplatePaths($event) : void
    {
        $paths = $event['paths'];
        $paths[] = __DIR__ . '/admin/themes/grav/templates';
        $event['paths'] = $paths;
    }

    /**
     * event is called when login button is pressed
     *
     * @param UserLoginEvent $event
     * @return void
     */
    public function userLoginAuthenticate(UserLoginEvent $event) : void
    {
        // credentials contain username and password in array Array ( [username] => [password] => )
        $credentials = $event->getCredentials();

        // if empty username -> ignore
        if($credentials['username'] == '' or $credentials['password'] == ''){
           $event->setStatus($event::AUTHENTICATION_FAILURE);
           return;
        }

        $username = $credentials['username'];
        $password = $credentials['password'];

        // Plugin parameters
        $tecart_url           = $this->config->get('plugins.tecart-session-login.tecart_url');
        $tecart_session_api   = $this->config->get('plugins.tecart-session-login.tecart_session_api');
        $proxy_ip             = $this->config->get('plugins.tecart-session-login.proxy_ip');
        $proxy_port           = $this->config->get('plugins.tecart-session-login.proxy_port');

        if (is_null($tecart_url) || is_null($tecart_session_api)) {
           throw new ConnectionException('FATAL: TecArt URL or session API entry missing in plugin configuration.');
        }

        $ch_url = $tecart_url.'/'.$tecart_session_api;

        // do the TecArt Session Authentication
        $request = $this->tecartSessionAuthentication($ch_url, $username, $password, $proxy_ip, $proxy_port);

        // if cURL error
        if (isset($request['error'])) {
            $this->grav['log']->error('plugin.tecart-session-login: ' .  $username . ' - ' . $request['error']);
            $event->setStatus($event::AUTHENTICATION_FAILURE);
            // Just return so other authenticators can take a shot...
            return;
        }

        // if authentication success
        if (isset($request['http_code']) && $request['http_code'] <= 299
            && isset($request['response'])
            && isset($request['response']['data']) && !empty($request['response']['data'])) {

            // Here you can further process the response, e.g. B. check certain values
            $sessionData = $request['response']['data'];

            // Create Grav User (no saving at this point - just serve user infos to make grav admin work)
            $grav_user = $this->createTecartGravUser($username, $sessionData);

            $grav_user->save();

            // Login
            $event->setUser($grav_user);

            $event->setStatus($event::AUTHENTICATION_SUCCESS);

            $event->stopPropagation();
            // Successful authentication, no need for further processing

        }
        // if authentication error
        else{
           $event->setStatus($event::AUTHENTICATION_FAILURE);
            // Authentication failed, no need for further processing
        }

    }

    /**
     * This gets fired if user fails to log in.
     * Allows plugins to include their own logic when user authentication failed.
     */
    public function userLoginFailure(UserLoginEvent $event)
    {
        // do something other than grav would do regular
    }

    /**
     * This gets fired if user successfully logs in.
     * Allows plugins to include their own logic when user logs in.
     */
    public function userLogin(UserLoginEvent $event)
    {
        // do something other than grav would do regular
    }

    /**
     * This gets fired on user logout.
     * Allows plugins to include their own logic when user logs out.
     */
    public function userLogout(UserLoginEvent $event)
    {
        // do something other than grav would do regular
    }

    /**
     * Authenticates with the TecArt CRM using a user session via the TecArt CRM API.
     * This function performs a cURL request to the specified URL with provided user credentials.
     * Optionally, it can be configured to use a proxy server for the cURL request.
     *
     * @param string $url The URL for the TecArt CRM API.
     * @param string $user The username for TecArt CRM authentication.
     * @param string $password The password for TecArt CRM authentication.
     * @param string|null $proxy_ip Optional. The IP address of the proxy server.
     * @param string|null $proxy_port Optional. The port number of the proxy server.
     * @return array An associative array containing the cURL request information or error information.
     */
    protected function tecartSessionAuthentication(string $url, string $user, string $password, string $proxy_ip = null, string $proxy_port = null) : array
    {
        $ch = curl_init();

        $token = base64_encode($user.":".$password);

        $headers = array(
            'Accept: application/json',
            'Authorization: Basic '.$token
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);                // set to false in production mode!
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERPWD, $user.":".$password);

        // Set up the proxy if provided.
        if (!empty($proxy_ip) && !empty($proxy_port)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
        }

        // Set up the proxy if provided.
        if (!empty($proxy_ip) && !empty($proxy_port)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $result = array('error' => 'cURL Error: '.curl_error($ch));
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result = array(
                'response' => json_decode($response, true),
                'http_code' => $httpCode
            );
        }

        curl_close($ch);

        return $result;
    }

    /**
     * This function creates or updates a Grav user based on the provided TecArt username. It is designed
     * to integrate with the TecArt CRM system for user authentication and management within Grav.
     *
     * Note: This function does not save the user object. It must be saved separately if persistence
     *       is required.
     */
    protected function createTecartGravUser(string $username, array $sessionData)
    {
        // Always creates user object. To check if user exists, use exists().
        $grav_user = $this->grav['accounts']->load(trim($username));

        $sessionData_group_ids = !empty($sessionData['group_ids']) ? $sessionData['group_ids'] : array();

        // Set permissions
        $permissions = array();

        // Default permissions
        $permissions['site']['login'] = true;
        $permissions['admin']['login'] = true;
        $permissions['admin']['pages'] = true;

        // Set user
        $grav_user['state']     = 'enabled';
        $grav_user['fullname']  = $sessionData['name'] ?? $username;        //is shown in admin panel
        $grav_user['email']     = $sessionData['email_address'] ?? '';
        $grav_user['language']  = $sessionData['user_language'] ?? 'de';
        $grav_user['groups']    = $this->getUserGroups($sessionData_group_ids);
        $grav_user['access']    = $permissions;

        // Get the group configuration from groups.yaml
        $groupsConfig = $this->grav['config']->get('groups');

        // Access by groups
        if (!empty($grav_user['groups'])) {
            // Assign permissions based on group membership
            foreach ($grav_user['groups'] as $group) {
                if (isset($groupsConfig[$group])) {
                    $groupPermissions = $groupsConfig[$group]['access'];
                    $permissions = $this->mergePermissions($permissions, $groupPermissions);
                }
            }
            // Setting the permissions for the user
            $grav_user['access'] = $permissions;
        }

        return $grav_user;
    }


    /**
     * Updates user's group membership based on group IDs from session data.
     *
     * @param array $sessionData_group_ids Array von Gruppen-IDs aus den Session-Daten.
     * @return void
     */
    protected function getUserGroups(array $sessionData_group_ids)
    {
        // get plugin settings
        $administratorGroupIds = $this->convertToCleanArray($this->config->get('plugins.tecart-session-login.group_administrator'));
        $editorGroupIds = $this->convertToCleanArray($this->config->get('plugins.tecart-session-login.group_editor'));
        $developerGroupIds = $this->convertToCleanArray($this->config->get('plugins.tecart-session-login.group_developer'));

        $userGroups = [];

        // Checking group IDs and assigning them to group names
        foreach ($sessionData_group_ids as $groupId) {
            if (in_array($groupId, $administratorGroupIds)) {
                $userGroups[] = 'Administrator';
            } elseif (in_array($groupId, $editorGroupIds)) {
                $userGroups[] = 'Editor';
            } elseif (in_array($groupId, $developerGroupIds)) {
                $userGroups[] = 'Developer';
            }
        }

        // Avoiding duplicate entries
        return array_unique($userGroups);
    }

    /**
     * merge permissions to ensure there are no unwanted overlaps or duplications.
     *
     * @param array $existingPermissions
     * @param array $newPermissions
     * @return array
     */
    protected function mergePermissions(array $existingPermissions, array $newPermissions)
    {
        foreach ($newPermissions as $key => $values) {
            if (!isset($existingPermissions[$key])) {
                $existingPermissions[$key] = $values;
            } else {
                foreach ($values as $subKey => $value) {
                    $existingPermissions[$key][$subKey] = $value;
                }
            }
        }

        return $existingPermissions;
    }

    /**
     * Converts a comma-separated list of IDs into an array, removing unnecessary spaces.
     *
     * @param string $groupIdsString Die kommaseparierte Liste von IDs.
     * @return array Das Array von bereinigten IDs.
     */
    private function convertToCleanArray($groupIdsString): array
    {
        return array_map('trim', explode(',', $groupIdsString));
    }

}
