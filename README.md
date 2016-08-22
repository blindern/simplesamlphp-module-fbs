# simplesamlphp module for fbs

## example configuration

```php
/*
 * Pålogging på Google Apps for UKA
 */
$metadata['google.com/a/blindernuka.no'] = array(
  'AssertionConsumerService' => 'https://www.google.com/a/blindernuka.no/acs',
  'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
  'simplesaml.nameidattribute' => 'gapps-mail',
  'simplesaml.attributes' => false,
  'saml20.sign.assertion' => true,
  'authproc' => array(
    10 => array(
      'class' => 'fbs:UKAGoogleApps',
      'accounts_url' => 'https://foreningenbs.no/intern/api/googleapps/accounts',
      'userfile' => dirname(__DIR__) . '/config/ukausers.txt'
    )
  )
);
```
