FOSOAuthServerBundle Configuration Reference
============================================

All available configuration options are listed below with their default values.

```yaml
bn01z_async_task:
    queue:
        use:                  zmq # One of "redis"; "zmq"; "custom"
        adapters:
            redis:
                connection:           'tcp://127.0.0.1:6379'
                queue_name:           task_queue
            zmq:
                frontend_connection:  'tcp://127.0.0.1:5560'
                backend_connection:   'ipc:///path/to/project/var/bn01z_async_task_workers.ipc'
    status:
        use:                  file # One of "file"; "redis"; "custom"
        adapters:
            file:
                save_dir:             /path/to/project/var/cache/dev/bn01z/async-http/status
            redis:
                connection:           'tcp://127.0.0.1:6379'
    web_socket:
        enabled:              true
        address:              '0.0.0.0:8080'
        notificator_connection: 'tcp://127.0.0.1:5555'
    attribute_name:       asyncTaskStatus
```

[Back to index](index.md)
