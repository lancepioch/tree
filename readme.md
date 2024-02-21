# 🌲 Laravel Forest

[![codecov](https://codecov.io/gh/lancepioch/tree/branch/master/graph/badge.svg)](https://codecov.io/gh/lancepioch/tree)
[![tests](https://github.com/lancepioch/tree/actions/workflows/laravel.yaml/badge.svg)](https://github.com/lancepioch/tree/actions/workflows/laravel.yaml)

![Trees](public/img/trees.png)

## Description
Connect your GitHub Repository to your Laravel Forge Server and Laravel Forest automatically deploys any new pull requests for you.

## Requirements

* PHP 8.2+

## Installation

1. Git Clone: `git clone git@github.com:lancepioch/tree.git`
2. Composer Install `composer install`
3. Environment Setup: `cp .env.example .env`
4. Artisan Migrate: `php artisan migrate`
5. Daemonize Horizon: `php artisan horizon`

## Demo Video
[![Demo Video](https://i.imgur.com/pJnISxo.png)](https://youtu.be/e48QJdcNrUY)
