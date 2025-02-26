# [opendor.me](https://opendor.me)

[![](https://img.shields.io/hsts/preload/opendor.me?style=flat-square)](https://hstspreload.org/?domain=opendor.me)
[![](https://img.shields.io/mozilla-observatory/grade/opendor.me?publish&style=flat-square)](https://observatory.mozilla.org/analyze/opendor.me)
[![](https://img.shields.io/security-headers?style=flat-square&url=https%3A%2F%2Fopendor.me)](https://securityheaders.com/?q=https%3A%2F%2Fopendor.me)

This is the repository for the [opendor.me](http://opendor.me). The code is entirely open source and licensed under [CC BY-NC-SA 3.0 License](LICENSE.md). Read the installation guide below to get started with setting up the app on your machine.

## Requirements

The following tools are required in order to start the installation.

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/)
- [Make](https://www.gnu.org/software/make/)

## Installation

1. Clone this repository with `git clone https://github.com/Astrotomic/opendor.me.git`
2. Run `cp .env.example .env` to copy .env file
3. Run `make setup` to setup local environment
4. Run `make start` to start the application

### Github Setup

To get Github authentication to work locally, you'll need to [register a new OAuth application on Github](https://github.com/settings/applications/new). Use `http://localhost:8080` for the homepage url and `http://localhost:8000/auth/github/callback` for the callback url. When you've created the app, fill in the ID and secret in your `.env` file in the env variables below. You should now be able to authentication with Github.

```
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
```

## Commands

Command | Description
--- | ---
`make composer-install` | Install composer dependencies
`make run <command>` | Run the desire command inside container
`make migrate` | Run the migration
`make npm-dev` | Build the dependecies
`make npm-install` | Install npm dependecies
`make stop` | Stop the running conatiners
