<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6                                                    |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2026 Issabel Foundation                                |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is Issabel Foundation     |
  +----------------------------------------------------------------------+
*/

/*
 * Registry of ACME domain-validation methods supported by the HTTPS
 * Certificate (Let's Encrypt) module.
 *
 * Installed as /usr/share/issabel/letsencrypt/providers.php, owned by root
 * and not writable by the web server user. Both the GUI and the privileged
 * helper (ssl_certbot) read it, so the method names and the credential field
 * names are validated against a single allowlist.
 *
 * Keys:
 *   group               'http' or 'dns', used by the UI to switch the help text
 *   label               name shown in the method selector
 *   summary             one-line explanation shown when the method is selected
 *   authenticator       certbot authenticator plugin (-a)
 *   webroot             webroot directory for the webroot authenticator
 *   credentials_provider  non-empty when the method needs a credentials INI
 *   credentials_file    file name inside /etc/letsencrypt/credentials
 *   propagation_default seconds to wait for DNS propagation (0 = not used)
 *   docs_url            provider documentation for the credentials
 *   fields              credential fields written to the INI file
 *
 * Field keys:
 *   name        INI key and POST field name
 *   type        text | password | number | select | checkbox
 *   label       visible label
 *   help        inline help shown under the control
 *   placeholder example shown inside the control
 *   required    whether certbot needs the value
 *   default     initial value
 *   min/max     numeric bounds (number fields)
 *   maxlength   maximum length (text/password fields)
 *   options     list of array('value' => ..., 'label' => ...) for select fields
 *   advanced    hide the field behind the "Advanced" toggle
 */

return array(
    'webroot' => array(
        'group'               => 'http',
        'label'               => 'HTTP-01 (webroot)',
        'summary'             => 'The certificate authority downloads a file over HTTP on port 80 from this server. TCP port 80 must be reachable from the Internet and the domain must point here.',
        'authenticator'       => 'webroot',
        'webroot'             => '/var/www/html',
        'credentials_provider' => '',
        'credentials_file'    => '',
        'propagation_default' => 0,
        'docs_url'            => 'https://letsencrypt.org/docs/challenge-types/',
        'fields'              => array(),
    ),
    'dns-cloudflare' => array(
        'group'               => 'dns',
        'label'               => 'DNS-01 (Cloudflare)',
        'summary'             => 'The certificate authority reads a TXT record created through the Cloudflare API. No inbound port is needed and wildcard names such as *.example.com are supported.',
        'authenticator'       => 'dns-cloudflare',
        'webroot'             => '',
        'credentials_provider' => 'cloudflare',
        'credentials_file'    => 'cloudflare.ini',
        'propagation_default' => 10,
        'docs_url'            => 'https://developers.cloudflare.com/fundamentals/api/get-started/create-token/',
        'fields'              => array(
            array(
                'name'        => 'dns_cloudflare_api_token',
                'type'        => 'password',
                'label'       => 'API Token',
                'help'        => 'Create an API token with Zone:Read and DNS:Edit on the zone that hosts this domain. The token is stored root-only and is never shown again.',
                'placeholder' => 'Paste the API token',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 512,
                'advanced'    => false,
            ),
        ),
    ),
    'dns-digitalocean' => array(
        'group'               => 'dns',
        'label'               => 'DNS-01 (DigitalOcean)',
        'summary'             => 'The certificate authority reads a TXT record created through the DigitalOcean API. No inbound port is needed and wildcard names such as *.example.com are supported.',
        'authenticator'       => 'dns-digitalocean',
        'webroot'             => '',
        'credentials_provider' => 'digitalocean',
        'credentials_file'    => 'digitalocean.ini',
        'propagation_default' => 10,
        'docs_url'            => 'https://docs.digitalocean.com/reference/api/create-personal-access-token/',
        'fields'              => array(
            array(
                'name'        => 'dns_digitalocean_token',
                'type'        => 'password',
                'label'       => 'API Token',
                'help'        => 'Create a personal access token with read and write access in the DigitalOcean control panel (API, Tokens). The token is stored root-only and is never shown again.',
                'placeholder' => 'Paste the API token',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 512,
                'advanced'    => false,
            ),
        ),
    ),
    'dns-linode' => array(
        'group'               => 'dns',
        'label'               => 'DNS-01 (Linode)',
        'summary'             => 'The certificate authority reads a TXT record created through the Linode API. No inbound port is needed and wildcard names such as *.example.com are supported.',
        'authenticator'       => 'dns-linode',
        'webroot'             => '',
        'credentials_provider' => 'linode',
        'credentials_file'    => 'linode.ini',
        'propagation_default' => 120,
        'docs_url'            => 'https://techdocs.akamai.com/cloud-manager/docs/manage-personal-access-tokens',
        'fields'              => array(
            array(
                'name'        => 'dns_linode_key',
                'type'        => 'password',
                'label'       => 'API Token',
                'help'        => 'Create a personal access token with read and write access to Domains in the Linode Cloud Manager. The token is stored root-only and is never shown again.',
                'placeholder' => 'Paste the API token',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 512,
                'advanced'    => false,
            ),
        ),
    ),
    'dns-ovh' => array(
        'group'               => 'dns',
        'label'               => 'DNS-01 (OVH)',
        'summary'             => 'The certificate authority reads a TXT record created through the OVH API. No inbound port is needed and wildcard names such as *.example.com are supported.',
        'authenticator'       => 'dns-ovh',
        'webroot'             => '',
        'credentials_provider' => 'ovh',
        'credentials_file'    => 'ovh.ini',
        'propagation_default' => 120,
        'docs_url'            => 'https://www.ovh.com/auth/api/createToken',
        'fields'              => array(
            array(
                'name'        => 'dns_ovh_endpoint',
                'type'        => 'select',
                'label'       => 'API Endpoint',
                'help'        => 'OVH API endpoint that matches your account: ovh-eu for Europe, ovh-ca for Canada, ovh-us for the United States. The other endpoints are for Kimsufi and So you Start accounts.',
                'placeholder' => '',
                'required'    => true,
                'default'     => 'ovh-eu',
                'options'     => array(
                    array('value' => 'ovh-eu',        'label' => 'ovh-eu'),
                    array('value' => 'ovh-ca',        'label' => 'ovh-ca'),
                    array('value' => 'ovh-us',        'label' => 'ovh-us'),
                    array('value' => 'kimsufi-eu',    'label' => 'kimsufi-eu'),
                    array('value' => 'kimsufi-ca',    'label' => 'kimsufi-ca'),
                    array('value' => 'soyoustart-eu', 'label' => 'soyoustart-eu'),
                    array('value' => 'soyoustart-ca', 'label' => 'soyoustart-ca'),
                ),
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_ovh_application_key',
                'type'        => 'text',
                'label'       => 'Application Key',
                'help'        => 'Application key created for the OVH API token. It is paired with the application secret and the consumer key.',
                'placeholder' => 'Paste the application key',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 255,
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_ovh_application_secret',
                'type'        => 'password',
                'label'       => 'Application Secret',
                'help'        => 'Application secret returned when the OVH API token is created.',
                'placeholder' => 'Paste the application secret',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 255,
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_ovh_consumer_key',
                'type'        => 'text',
                'label'       => 'Consumer Key',
                'help'        => 'Consumer key returned when the OVH API token is created. It authorizes the application to change DNS records.',
                'placeholder' => 'Paste the consumer key',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 255,
                'advanced'    => false,
            ),
        ),
    ),
    'dns-rfc2136' => array(
        'group'               => 'dns',
        'label'               => 'DNS-01 (RFC 2136)',
        'summary'             => 'The certificate authority reads a TXT record created with a TSIG-signed RFC 2136 dynamic update. No inbound port is needed and wildcard names are supported.',
        'authenticator'       => 'dns-rfc2136',
        'webroot'             => '',
        'credentials_provider' => 'rfc2136',
        'credentials_file'    => 'rfc2136.ini',
        'propagation_default' => 60,
        'docs_url'            => 'https://certbot-dns-rfc2136.readthedocs.io/',
        'fields'              => array(
            array(
                'name'        => 'dns_rfc2136_server',
                'type'        => 'text',
                'label'       => 'DNS Server',
                'help'        => 'IP address of the DNS server that accepts dynamic updates. A host name is not accepted.',
                'placeholder' => '192.0.2.53',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 64,
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_rfc2136_port',
                'type'        => 'number',
                'label'       => 'Port',
                'help'        => 'Port of the DNS server. The standard port is 53.',
                'placeholder' => '53',
                'required'    => false,
                'default'     => '53',
                'min'         => 1,
                'max'         => 65535,
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_rfc2136_name',
                'type'        => 'text',
                'label'       => 'TSIG Key Name',
                'help'        => 'Name of the TSIG key as configured in the DNS server, for example acme-key.',
                'placeholder' => 'acme-key',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 255,
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_rfc2136_secret',
                'type'        => 'password',
                'label'       => 'TSIG Secret',
                'help'        => 'TSIG secret in base64, exactly as it appears in the key file, without the algorithm name.',
                'placeholder' => 'Paste the TSIG secret',
                'required'    => true,
                'default'     => '',
                'maxlength'   => 1024,
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_rfc2136_algorithm',
                'type'        => 'select',
                'label'       => 'TSIG Algorithm',
                'help'        => 'Algorithm associated with the TSIG key.',
                'placeholder' => '',
                'required'    => false,
                'default'     => 'HMAC-SHA512',
                'options'     => array(
                    array('value' => 'HMAC-MD5',     'label' => 'HMAC-MD5'),
                    array('value' => 'HMAC-SHA1',    'label' => 'HMAC-SHA1'),
                    array('value' => 'HMAC-SHA224',  'label' => 'HMAC-SHA224'),
                    array('value' => 'HMAC-SHA256',  'label' => 'HMAC-SHA256'),
                    array('value' => 'HMAC-SHA384',  'label' => 'HMAC-SHA384'),
                    array('value' => 'HMAC-SHA512',  'label' => 'HMAC-SHA512'),
                ),
                'advanced'    => false,
            ),
            array(
                'name'        => 'dns_rfc2136_sign_query',
                'type'        => 'checkbox',
                'label'       => 'Sign Query',
                'help'        => 'Sign the DNS query in addition to the update. Rarely needed.',
                'placeholder' => '',
                'required'    => false,
                'default'     => '',
                'advanced'    => true,
            ),
        ),
    ),
    'dns-hook' => array(
        'group'               => 'dns',
        'label'               => 'DNS-01 (Custom hook)',
        'summary'             => 'The certificate authority reads a TXT record published by a hook script installed by the administrator in /etc/letsencrypt/hooks. No inbound port is needed and wildcard names are supported.',
        'authenticator'       => 'manual',
        'webroot'             => '',
        'credentials_provider' => '',
        'credentials_file'    => '',
        'propagation_default' => 30,
        'docs_url'            => '',
        'hook'                => true,
        'fields'              => array(),
    ),
);
