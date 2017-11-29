Dotmailer
=======

Allows you to create dotmailer fields on entities.

REQUIREMENTS
-------------
1. You need to have a dotmailer account.
2. You need to create a dotmailer api user.
3. You must have a writable address book in dotmailer.
3. You need to install this module via composer.

INSTALLATION
-------------
To use this module you have to download it using composer and then install it.

   ```sh
    composer require "drupal/dotmailer"
   ```
USAGE
-------------

* Add a dotmailer api user by visiting the following url
admin/structure/dotmailer_api_user

* Assign an appropriate address book to the api user you 
can click Dotmailer address books tab.

* Create a dotmailer field against the user entity just 
like you would normally create a field. 
 
* Complete all the usual settings.

* The field should now appear on the user entity.

* Ensure that you do not use the Test address book.
  This is not writable by the api.

Still to do in order of priority.
-------------
1. Finish the integration to webform (not usable).
2. Leverage caching more.
3. Finish contact data fields.
4. Automated tests.

CREDITS
-------------
Maintainer and developer:**tresti88**

Development sponsored by:**One**

For professional support and development services contact: **michaeltrestianu@gmail.com**
