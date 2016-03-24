# phalcon-query

## Installation

### Dependencies

## Usage

### Initialize

In service.php, It is registered act as a service in `Injuery dependencies` container.

```php

$input = new Input();
$criteria = new Criteria();

```

In query request,

```php

$rules = [
    'phone' => ['trim', 'required']
];

$sorts = [
    'created' => -1
];

$data  = $input->process($request, $rules);
$query = $criteria->get($data, $sort);
$items = $model->find($query);

```

Supported filter types:

1. trim
1. required
1. str2arr
1. mongoId
1. mongodate
1. like
1. double
1. json
