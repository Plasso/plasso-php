# Plasso Backend Client
PHP client for [Flexkit](https://plasso.com/flexkit)

## Purpose
This client will automatically restrict access to pages you want to protect.
Only your Plasso members will be able to access the pages you choose to protect, by logging in or signing up.

## How to use it
1. Copy & paste the contents of `plasso.php` into your code base (or include it).
2. Uncomment the initalization line to ensure the code runs. This line: `$Plasso = new Plasso( ...`
3. (Optional) You can access the Plasso Members's `data` with: `$Plasso->memberData`

## Where to place the client code
At the **very** beginning of your script, on the pages you want to protect.

See the [full documentation](https://plasso.com/docs) on using this with Flexkit.
Check out the [Help Center](https://help.plasso.com/) for any other questions.
