# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 3.0.0 - 2017-12-22
### Added
- Support for a custom sender per e-mail.

### Upgrade from 2.x to 3.x

3.0.0 added support for a custom sender per e-mail. To update please run the following command:

```bash
php artisan migrate
```

## 2.0.0 - 2017-12-14
### Added
- Support for multiple recipients, cc and bcc addresses.
- Support for mailables (*)
- Support for attachments
- New method `later`

*= Only works for Laravel versions 5.5 and up because 5.5 finally introduced a method to read the mailable body.

### Fixed
- Bug causing failed e-mails not to be resent

### Upgrade from 1.x to 2.x
Because 2.0.0 introduced support for attachments, the database needs to be updated. Simply run the following two commands after updating your dependencies and running composer update:

```bash
php artisan migrate
```

## 1.1.3 - 2017-12-07
### Fixed
- Created a small backwards compatibility fix for Laravel versions 5.4 and below.

## 1.1.2 - 2017-11-18
### Fixed
- Incorrect auto discovery namespace for Laravel 5.5


## 1.1.1 - 2017-08-02
### Changed
- Only dispatch `before.send` event during unit tests

## 1.1.0 - 2017-07-01
### Added
- PHPUnit tests
- Support for CC and BCC

## 1.0.0 - 2017-06-29
### Added

- Initial release of the package
