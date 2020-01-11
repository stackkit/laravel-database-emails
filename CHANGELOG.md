# Releases
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 4.1.1 - 2020-01-11

**Fixed**

- Fixed inline attachments could not be stored
- Fixed PHP 7.4 issue when reading empty Mailable from address

## 4.1.0 - 2019-07-13

**Added**

- Option to send e-mails immediately after calling send() or later()

**Changed**

- attach() and attachData() will no longer add empty or null files

## 4.0.2 - 2019-01-01

**Fixed**

- Fixed regression bug (testing mode)

## 4.0.1 - 2018-12-31

**Added**

- New environment variable `LARAVEL_DATABASE_EMAILS_TESTING_ENABLED` to indicate if testing mode is enabled (*)

**Fixed**

- Fixed issue where Mailables would not be read correctly
- Config file was not cachable (*)

(*) = To be able to cache the config file, change the 'testing' closure to the environment variable as per `laravel-database-emails.php` config file.

## 4.0.0 - 2018-09-15

**Changed**

- Changed package namespace

**Removed**

- Removed resend/retry option entirely
- Removed process time limit

## 3.0.3 - 2018-07-24

**Fixed**

- Transforming an `Email` object to JSON would cause the encrpyted attributes to stay encrypted. This is now fixed.

## 3.0.2 - 2018-03-22

**Changed**

- Updated README.md

**Added**

- Support for process time limit

---

## 3.0.1 - 2018-03-18

**Changed**

- Updated README.md
- Deprecated `email:retry`, please use `email:resend`

---

## 3.0.0 - 2017-12-22

**Added**

- Support for a custom sender per e-mail.

**Upgrade from 2.x to 3.x**

3.0.0 added support for a custom sender per e-mail. To update please run the following command:

```bash
php artisan migrate
```

---

## 2.0.0 - 2017-12-14

**Added**

- Support for multiple recipients, cc and bcc addresses.
- Support for mailables (*)
- Support for attachments
- New method `later`

*= Only works for Laravel versions 5.5 and up because 5.5 finally introduced a method to read the mailable body.

**Fixed**
- Bug causing failed e-mails not to be resent

**Upgrade from 1.x to 2.x**
Because 2.0.0 introduced support for attachments, the database needs to be updated. Simply run the following two commands after updating your dependencies and running composer update:

```bash
php artisan migrate
```

---

## 1.1.3 - 2017-12-07

**Fixed**

- Created a small backwards compatibility fix for Laravel versions 5.4 and below.

---

## 1.1.2 - 2017-11-18

**Fixed**

- Incorrect auto discovery namespace for Laravel 5.5

---

## 1.1.1 - 2017-08-02

**Changed**

- Only dispatch `before.send` event during unit tests

---

## 1.1.0 - 2017-07-01

**Added**

- PHPUnit tests
- Support for CC and BCC

---

## 1.0.0 - 2017-06-29

**Added**

- Initial release of the package
