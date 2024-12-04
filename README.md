# Php task

Task for implementing ledger balance update system that can handle minimum 1000 requests per minute.
Based on symfony and docker, implemented optimistic lock for transactions.

## Stack

* PHP 8.3
* Symfony 6.4 LTS

## Requirements

* [Docker](https://www.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)
* [Git](https://git-scm.com/)
* [Make](https://www.gnu.org/software/make/)

## Get started

1. Clone the repository 

```bash
git clone https://github.com/YevgeniyBistrisky/Php-task.git php-task
```

2. Start the app

```bash
cd php-task
cp .env.example .env
make all
```

3. Benchmark that will try to create ledgers and count transactions per minute

```bash
make benchmark
```

## Tests

```bash
make db-create-test
make php
bin/phpunit
```

## Static/codestyle analysis

```bash
make pre-commit
```


## Usage

The app is running on localhost:80 or localhost:443
To create ledger use `POST` request to `/ledgers` endpoint with empty body. It will return ledger id and wallet information
```json
{
  "id": "1efb207d-dff2-6336-ada6-09d5d0db0966",
  "wallets": [
    {
      "currency": "UAH",
      "createdAt": "2024-12-04T06:20:33+00:00",
      "updatedAt": "2024-12-04T06:20:33+00:00",
      "amount": 0
    }
  ]
}
```
To create transaction use `POST` request to `/transactions` endpoint with body:
```json
{
  "ledgerId": "1efb1674-acbf-6e54-886f-21a6da2ff20d",
  "currency": "UAH",
  "operationType": "credit",
  "amount": "1251.23",
  "transactionId": "any string"
}
```
OperationType must be either debit or credit, currency only UAH(for now), ledgerId must be valid ledger id obtained from step one.

To check account balance use `GET` request to `/balance/{ledgerId}` endpoint. LedgerId must be valid ledger id obtained from step one.
```json
[
  {
    "currency": "UAH",
    "createdAt": "2024-12-04T06:20:33+00:00",
    "updatedAt": "2024-12-04T06:20:33+00:00",
    "amount": 0
  }
]
```
The app is ready for multi currency handling.

