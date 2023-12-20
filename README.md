# Eloquent Event Logger

Eloquent Event Logger is a logging tool for Laravel's Eloquent ORM. It automatically records and logs events for Laravel Eloquent model operations such as creation, updates, and deletions. Detailed event logs help facilitate debugging and keep track of changes made to models over time. It's a tool for monitoring your application's data layer, ensuring data integrity, and aiding in development.

## Installation

Install the package via Composer:

```bash
composer require wcg-package/eloquent-event-logger
```

## Configuration

Once the package is installed, Laravel's service provider should be automatically registered. If not, you can manually register the service provider in your `config/app.php` file:

```php
'providers' => [
    // Other providers...
    WcgPackage\EloquentEventLogger\EloquentEventServiceProvider::class,
],
```

## Usage

The package automatically logs events for Eloquent models in the "Models" directory. It logs updates, creations, and deletions, providing detailed information about the changes made to the models.

### Logging Events

The following events are logged:

- **Model Updates:** Logs the old and new values of the updated model.
- **Model Creations:** Logs the details of the model being created.
- **Model Deletions:** Logs when a model is being deleted.

### Log File Location

Logs are stored in the `storage/logs` directory, organized by date and model name.


## Logging Levels

Logs are created with different levels based on the type of event:

- **INFO:** Model updates, creations, and deletions.
- **ERROR:** Errors that occur during the logging process.

## Customization

You can customize the logging behavior by extending the `EloquentEventServiceProvider` class and overriding its methods.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

**Note:** Ensure that your application is set up to handle logging according to your needs, especially in production environments.