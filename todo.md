# TODO

Modeled after:

- https://github.com/fly-apps/dockerfile-rails
- https://github.com/fly-apps/dockerfile-node
- https://github.com/fly-apps/dockerfile-django

The primary purpose of this is to generate a Dockerfile that can run a production-ready PHP application.

The intial aspects of this will be a Dockerfile template file (blade?) and a CLI application that asks users for specific inputs and adjusts the resulting Dockerfile based on those options.

(It should ask if users want to overwrite any existing Dockerfile (or choose not to as a default)).

The current "Gold Standard" and a good place to show the types of options that may be useful is the Rails generator: https://github.com/fly-apps/dockerfile-rails

## Flyctl

This should eventually be useful for flyctl. Right now, for rails, flyctl will install the `dockerfile-rails` lib as a gem, and generate the Dockerfile based on that (todo: Check the validity of this statement). Flyctl then does some extra processing against the generated file(s) and sets some other options to make a user's deployments as smooth as possible.

