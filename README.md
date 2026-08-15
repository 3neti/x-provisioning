# x-provisioning

`3neti/x-provisioning` provides the governed authority lifecycle used by
x-change. It owns immutable provisioning requests, maker-checker approval,
opaque invitations, verified acceptance, activation, and commissioning seats.

Provisioning is not payment. An invitation may be correlated with a separate
Treasury grant, but accepting authority never creates liquidity or moves money.

## Core lifecycle

```text
draft → awaiting approval → approved → offered → activation pending → activated
```

The maker freezes the requested authority. An independent checker approves or
rejects that exact snapshot. Issuance creates a one-time, high-entropy claim
token whose hash alone is stored. A verified claim binds the candidate and may
activate automatically when the approved profile permits it.

The package intentionally delegates identity verification and concrete role
activation through contracts. Applications must bind those contracts to their
onboarding and authorization systems.
