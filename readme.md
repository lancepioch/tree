[![Build Status](https://travis-ci.org/lancepioch/tree.svg?branch=master)](https://travis-ci.org/lancepioch/tree)
[![codecov](https://codecov.io/gh/lancepioch/tree/branch/master/graph/badge.svg)](https://codecov.io/gh/lancepioch/tree)

# Laravel Forest

![Trees](public/img/trees.png)

## Description
Connect your Github Repository to your Laravel Forge Server and Laravel Forest automatically deploys any new pull requests for you.

## Demo Video
[![Demo Video](https://i.imgur.com/pJnISxo.png)](https://youtu.be/e48QJdcNrUY)

## Installation

1. Git Clone: `git clone git@github.com:lancepioch/tree.git`
2. Composer Install `composer install`
3. Environment Setup: `cp .env.example .env && vim .env`
4. Artisan Migrate: `php artisan migrate`
5. Daemonize Horizon: `php artisan horizon`

## License
```
MIT License

Copyright (c) 2018 Lance Pioch

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
