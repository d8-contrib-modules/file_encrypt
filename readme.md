# File Encrypt
The file encrypt module allows you to encrypt files uploaded via Drupal using
the encrypt and key modules. When the files are requested, they'll get decrypted
automatically.

## Configure file encrypt

Ideally first configure a different path at which the encrypted files should be
stored. Similar to private files you need an entry in settings.php for that:
```
$settings['encrypted_file_path'] = '';
```

The next step is to go to your file/image field settings and click on
"field settings". Under upload destination choose 'Encrypted files'.

## Encrypt file metadata

For encrypting metadata like title and description use the field_encrypt method.
@TODO

## Architecture

@TODO


