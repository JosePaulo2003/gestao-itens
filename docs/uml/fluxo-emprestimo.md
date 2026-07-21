# Fluxo de emprestimo

```mermaid
stateDiagram-v2
    [*] --> Solicitada
    Solicitada --> Retirada: gestor aprova retirada
    Solicitada --> Cancelada: gestor cancela
    Retirada --> Devolvida: gestor registra devolucao
    Retirada --> Bloqueada: prazo vencido ou infracao
    Bloqueada --> Retirada: bloqueio encerra
    Devolvida --> [*]
    Cancelada --> [*]
```
