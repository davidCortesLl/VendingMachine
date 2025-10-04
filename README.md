# Vending Machine API

## Description

This API simulates the operation of a vending machine. It allows you to insert coins, select products, return inserted money, and configure the inventory and available coins. Persistence can be in-memory or Redis, and it is ready to be easily run in a Docker environment.

## API Endpoints

### 1. Insert Coin
- **POST** `/insert-coin`
- **JSON Body:**
```json
{
  "value": 0.10
}
```
- **Response:** Updated machine state or validation error.

### 2. Return Inserted Coins
- **POST** `/return-coin`
- **JSON Body:** _(empty)_
- **Response:** Returned coins and updated state.

### 3. Select Product
- **POST** `/select-item`
- **JSON Body:**
```json
{
  "selector": "1"
}
```
- **Response:**
  - If there is enough money: product delivered, change, and updated state.
  - If not: insufficient balance error.

### 4. Configure Inventory and Coins (service)
- **POST** `/service/set-machine`
- **JSON Body:**
```json
{
  "items": [
    { "selector": "1", "name": "Water", "price": 0.65, "count": 5 },
    { "selector": "2", "name": "Juice", "price": 1.00, "count": 5 }
  ],
  "coins": [
    { "value": 0.10, "count": 10 },
    { "value": 0.25, "count": 10 }
  ]
}
```
- **Response:** Updated machine state.

## Example Responses

- **Success:**
```json
{
  "status": {
    "items": [...],
    "coins": [...],
    "insertedMoney": [...]
  }
}
```
- **Error:**
```json
{
  "error": "Descriptive error message"
}
```

## Running the Application with Docker

1. **Start the services:**
   ```sh
   make docker-up
   ```
2. **Run the tests:**
   ```sh
   make test
   ```
3. **Analyze the code with PHPStan:**
   ```sh
   make phpstan
   ```
4. **Get test coverage:**
   ```sh
   make coverage
   ```
5. **Get HTML coverage report:**
   ```sh
   make coverage-html
   ```
6. **Stop and clean up the services:**
   ```sh
   make docker-down
   ```

The API will be available on the port configured in `docker-compose.yml` (by default, usually 80 or 8080). You can test the endpoints using tools like Postman or curl.
