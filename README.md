rcomexpress
===========

register.com module for whmcs

Initial work,  not suitable for public use yet.

(c) Copyright Karl Gray 2014

This is a rough module for register.com API access for WHMCS
There is no error checking and minimal validation of incoming data as it is 
assumed that WHMCS will have pre-sanitised input before passing it to 
us.  Errors reported back from the registrar are passed to whmcs if 
available.

I wrote this because the module that comes with whmcs is borked and has 
been for years.  Contacts, nameservers and EPP code options didn't work.

The initial version works on the few tests I have done.  Please see the 
TODO file for details of work I want to complete.

Please note, I am not a developer and have done this only out of 
frustration.  If you don't like my programming style or spot any of the 
many things that I could have done better please feel free to submit fixes.

I have called this module rcomexpress to avoid the original register.com 
module overwriting any fixes.



TODO:
* Add logging option - add it to config array and change logging options to conditionals.  Add more loggin options.
* check to see if any refactoring can be done for example combining the duplicated xml builder code into a function.
* Add curl error checking and reporting
* add sync functions for transfers and expiry dates

