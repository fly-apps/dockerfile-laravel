# Overview

Provides a PHP generator to produce Dockerfiles and related files. It's target is mostly Laravel, but should work for most PHP applications.

## Usage

TBD


# Local Development

This project provides a CLI application that generates a Dockerfile for your Laravel applications! Feel free to make contributions to this project to further improve its features. Of course, you've got to commit to some local development on your contribution, first.

## Get Familiar with These
Where do you start? First, get familiar with the VIP files of this project:
1. [app/Commands/GenerateCommand.php](https://github.com/fly-apps/dockerfile-laravel/blob/d3254571d874b24340d614ff62c931d07495365e/app/Commands/GenerateCommand.php#L8)
    - This is the entrypoint to the application. This is in charge of generating a Dockerfile in a project's base directory based on the dockerfile.blade.php template, and the flags passed to it.

2. [resources/views/dockerfile.blade.php](https://github.com/fly-apps/dockerfile-laravel/blob/d3254571d874b24340d614ff62c931d07495365e/resources/views/dockerfile.blade.php#L1)
    - This is the template used to generate a Dockerfile 


## Configuration
Alright, now that you're familiar with the important files to commit your changes, you can make changes right, right?

Not quite! Before diving into making those changes, there's some to-do's to get you setup with local development on the package:

1. Clone the repository
2. Get the repo dependencies with `composer install`
3. Make your changes
4. Provide the proper permission to the box package used by Laravel Zero framework: `chmod 755 vendor/laravel-zero/framework/bin/box`. 


## Testing Changes

Once you have your changes, you can test them locally by running `php dockerfile-laravel generate`. This will call the command found in `app/Commands/GenerateCommand.php`. 

## Building Changes

Great! You've tested your changes locally. It's time to re-build the stand-alone application for the repository, so that your changes get included. 

Simply run the build command:

```
php dockerfile-laravel app:build --build-version=1.0
```

### Build View Changes 
If changes were made in any of the view files used by the package, make sure to clear the cached views( For now, manually deleting them--sorry folks! ). The view cache path can be configured in `config/view.php`. If you don't delete cached views, your fresh changes are unlikely to be included in the features of the build made from your changes.

So, if your view changes don't seem to be working, do delete any cached view files you find, then re-build your changes.

### Testing the newly-Built Application
Now that you've built your changes into the project's stand alone application binary, you have to test this out too. Testing local changes shouldn't be enough!

Simply, in any directoy of your choosing run the following:
```
<path-to-project-dir>/builds/dockerfile-laravel generate
```

And that's it!

# Contribution

Make a Pull Request please. The Laravel team at Fly.io will review your PR, this helps the repository a ton, your contribution is much appreaciated---thanks so much!