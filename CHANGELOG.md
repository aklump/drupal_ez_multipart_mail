# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Roadmap

- Add support for attachments: https://symfony.com/doc/current/components/mime.html

## [0.2] - 2022-04-06

### Changed

- Debug mode now prints to the screen as a message, but allows the email to be sent. Previously printed to screen and then exited the request; no email being sent.

### Fixed

- Bug with incorrect boundary header.
