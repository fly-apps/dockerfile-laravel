# TODO

The primary purpose of this is to generate a Dockerfile that can run a production-ready PHP application.

Modeled after:

- https://github.com/fly-apps/dockerfile-rails
- https://github.com/fly-apps/dockerfile-node
- https://github.com/fly-apps/dockerfile-django

We can also take Docker setup ideas from the Rails/Node/Django setup in addition to the already-created Laravel Dockerfile setup created via base image [laravel-docker](https://github.com/fly-apps/laravel-docker) and resulting Dockerfile from the [flyctl scanner](https://github.com/superfly/flyctl/blob/master/scanner/templates/laravel/Dockerfile).

The intial work of this project will be to generate a Dockerfile template (blade?) via a CLI application that asks users for specific inputs and adjusts the resulting Dockerfile based on answers to that input.

The current "Gold Standard" and a good place to copy possible options/features is the Rails generator: https://github.com/fly-apps/dockerfile-rails

## Flyctl

This should eventually be useful for flyctl. Right now, for rails, flyctl will install the `dockerfile-rails` lib as a gem, and generate the Dockerfile based on that (todo: Check the validity of this statement). Flyctl then does some extra processing against the generated file(s) and sets some other options to make a user's deployments as smooth as possible.

## Things

- [ ] Remove references to "fly" stuff so this can stand alone from fly as well?
- 
