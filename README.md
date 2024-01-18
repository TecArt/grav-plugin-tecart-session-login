[![TecArt GmbH](tecart-logo-rgba_h120.png)](https://www.tecart.de)
# TecArt Session Login Plugin

The **TecArt Session Login** Plugin for [Grav CMS](http://github.com/getgrav/grav) provides authentication using the TecArt Session API via cURL in your [Admin Plugin](https://github.com/getgrav/grav-plugin-admin).

To send a request using the TecArt Session API, you will need your TecArt API documentation

## Installation

Installing the TecArt Session Login plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method allows you to quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the [Admin Plugin](https://github.com/getgrav/grav-plugin-admin).

### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav installation, and enter:

    bin/gpm install tecart_session_login

This will install the TecArt Session Login plugin into your `/user/plugins` directory within [Grav CMS](http://github.com/getgrav/grav). Its files can be found under `/your/site/grav/user/plugins/tecart_session_login`.

### Manual Installation

To install the plugin manually, download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `tecart_session_login`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/tecart_session_login

> NOTE: This plugin is a modular component for [Grav CMS](http://github.com/getgrav/grav) and requires other plugins to operate:

    - { name: grav, version: '>=1.7.23' }
    - { name: login, version: '>=3.5.3' }
    - { name: admin, version: '>=1.10.23' }

### Admin Plugin

You can install the plugin directly by browsing the `Plugins` menu and clicking on the `Add` button.

## Configuration

Here is the default configuration and an explanation of available options:

```yaml
enabled: false                                                   - Plugin is disabled by default to allow configuration
tecart_url: https://domain.tecart.de                             - URL to your TecArt CRM
tecart_session_api: /rest_v2/index.php/5.4.68/session/           - TecArt Session API path - if changes are required
proxy_ip: null                                                   - Optional
proxy_port: null                                                 - Optional
group_administrator: '4,6'                                       - TecArt User Group Ids for Grav Group administrator
group_editor: '9'                                                - TecArt User Group Ids for Grav Group editor
group_developer: '22'                                            - TecArt User Group Ids for Grav Group developer
```

Note that if you configure this plugin with the Admin Plugin, a file with your configuration named tecart_session_login.yaml will be saved in the `user/config/plugins/` folder once the configuration is saved in the Admin.

Note that if you configure this plugin manually, you should copy the `user/plugins/tecart_session_login/tecart_session_login.yaml` to `user/config/plugins/tecart_session_login.yaml` and only edit that copy.

## Credits

This is also an extension of the [Login plugin](https://github.com/getgrav/grav-plugin-login).

## To Do

- N/A

## Known Issues

- N/A
