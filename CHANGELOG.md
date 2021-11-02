# Changelog

## Unreleased

## 0.3.1 - 2021-11-02

### Added
- Add some public hooks that let a developer insert their own blocks.

## 0.3.0 - 2021-09-18

### Changed
- Set the Torchlight environment to "build" so the build will fail if Torchlight fails.
- Throw an exception if there are Torchlight placeholders left behind in the build directory. 

### Fix
- Code blocks that were indented instead of fenced did not get replaced. They do now!   

## 0.2.1 - 2021-08-03

### Added
- Add option to set theme per block by adding `lang:theme` e.g. `php:github-light` in markdown.

### Changed
- Chunk blocks into groups of 50, since Jigsaw sites can be massive.

## 0.2.0 - 2021-08-02

### Changed
- Bumped minimum version of `torchlight/torchlight-laravel` to `0.5.0`

## 0.1.3 - 2021-07-28

### Added
- Added the ability to send `options` from the config file to the API.

### Changed
- Bumped minimum version of `torchlight/torchlight-laravel` to `0.4.6`

## 0.1.2 - 2021-07-18

### Changed
- Increase the default timeout to 15 seconds to cover large Jigsaw sites.

## 0.1.1 - 2021-06-17

### Changed
- The `torchlight.php` config file now defaults to an environment variable for the token.

### Fixed
- Torchlight caused an error if there were no code blocks whatsoever.