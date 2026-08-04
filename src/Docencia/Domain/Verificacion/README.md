# Domain / Verificacion — DO-02a

Caso de uso de verificación automática de atinencia. El schema compartido ya
fija los 4 resultados posibles en `verificaciones_atinencia.resultado`:
`Atinente, No Atinente, Nota técnica, Sin catálogo` (+ `es_provisional` para
vigencia futura). Snapshot inmutable por verificación (`catalogo_atinencia_id`
NULL solo cuando el resultado es `Sin catálogo`) — confirma D11.

Estado: no iniciado.
