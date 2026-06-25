# DomainNameAPI Integration Notes

## Verified PHP SDK methods

The DomainNameAPI PHP SDK repository (`domainreseller/php-dna`) exposes domain contact methods through `DomainNameAPI_PHPLibrary` and the SOAP/REST clients:

- `getContacts($domainName)`
- `saveContacts($domainName, $contacts)`

These methods are domain contact operations. They are not provider customer account create/search/details endpoints.

## Adapter rule

The `domainnameapi` provider must not invent a customer account create endpoint or method. In this module, the existing `customer/create` queue action for DomainNameAPI is treated as TR domain contact preparation only:

1. Validate that the domain is one of the configured TR extensions.
2. Build the SDK contact payload with `Administrative`, `Billing`, `Technical`, and `Registrant` contacts.
3. Mark the provider customer mapping as `contact_ready` without storing a `provider_customer_id`.
4. Use `saveContacts($domainName, $contacts)` only for a real domain contact update flow.
5. Use `getContacts($domainName)` only for a real domain contact details flow.

## Security

Provider responses and queue results must not persist or log credential-like fields such as:

- `api-key`
- `api_key`
- `ApiKey`
- `passwd`
- `password`
- `Password`
- `auth-code`
- `auth_code`
- `AuthCode`

## Open verification note

No DomainNameAPI PHP SDK method was verified for provider-level customer account creation. Until an official SDK/API method is found, customer account create/search/details must remain unavailable for DomainNameAPI and must not be replaced with a guessed endpoint.
