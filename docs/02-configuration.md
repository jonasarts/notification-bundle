Configure the bundle options
============================

## Configuration options

```yaml
#app/config/config.yml
notification:
    template:
        loader: filesystem
        path: ../templates
    from:
        address: nobody@domain.tld
        name: Mr. Nobody
    sender:
        address: null
        name: null
    reply_to:
        address: null
        name: null
    return_path: null
    subject_prefix: null
```
