
# Core System
Core System includes following essential parts of running the software. It might be extended when new services are extended and developed in future.

- Setting
- User
- Patient
- Category
- UoM
- Item
- Billing

## Requirements  
- PHP 8.1 or higher
- Node 16.x

## To install the composer packages
1. Run `composer install`
2. Run `php artisan key:generate`

## Configure .env file
refer to .env.example file. Especially need to set 

## Run
`php artisan serve`

## Generate API documentation after new features added
`php artisan l5-swagger:generate`

## Required Permissions
`sudo chmod -R o+rw bootstrap/cache`
`sudo chmod -R o+rw storage`
