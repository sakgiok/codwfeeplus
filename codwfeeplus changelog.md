## v1.1.9
* Added compatibility for PS 8.0.4.
## v1.1.8
* Fixed a bug that prevented the module from working when a fresh install was made.
## v1.1.7
* Added "States" field in conditions to validate against the state of the delivery address.
## v1.1.6
* Fixed a bug for some PS versions < 1.6.1.0
* Added provision for PS version 1.7.6.0
## v1.1.5
* Added compatibility for PS 1.6.0.6.
* Added compatibility for PS 1.6.1.24
## v1.1.4
* Fixed a bug when a decimal value in the "total cart value" condition's field wasn't valid.
* Fixed a bug with cart summary not shown correctly on some earlier Prestashop 1.7 versions.
* Taxes are displayed correctly in cart summary on Prestashop 1.7
* Improved compatibility with various Prestashop versions when adding the fee to carrier's fee.
* When reordering an order with COD Product, it will be deleted from the cart automatically (except for some early 1.7 PS versions).
## v1.1.3
* Fixed a serious bug in payment validation.
## v1.1.2
* Added option for order state after payment.
* Added some sanity checks for some parameters that might change in Prestashop from the user (tax rules, custom order states, countries , zones etc).
* Fixed a bug with ValidateOrder function.
* Visual improvements.
## v1.1.1
* Taxes are now displaying correctly in PS1.7 cart summary and in PS 1.6-1.7 invoice.
* Added buttons in condition parameter editing.
## v1.1.0
* Added a "Condition Type" field in conditions that allows you to use the validation of the condition to disable the module (e.g. for specific carriers etc).
* Fixed a bug that a global product integration method might give an error.
* Redesigned the list colors.
## v1.0.10
* Fixed a bug when importing conditions.
* Fixed a bug where not all carriers where displayed in the conditions.
* Changed 'Manufacturers' to 'Brands' for PS 1.7.xx.
## v1.0.9
* Added real time update of cart summary in PS 1.7.xx.