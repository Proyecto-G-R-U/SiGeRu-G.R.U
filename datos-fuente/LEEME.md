# Contornos de los municipios de Montevideo

`municipios-montevideo.geo.json` son los contornos ya procesados que usa el
frontend para asignar la zona de un contenedor automáticamente. Están
embebidos en `frontend/index.html` (constante `ZONAS_GEO`); esta copia queda
acá sólo como respaldo y para dejar documentado de dónde salieron.

## Origen

Shapefile oficial **"Límites de Municipios de Montevideo"**, publicado por la
Intendencia de Montevideo en el Catálogo Nacional de Datos Abiertos:

https://catalogodatos.gub.uy/dataset/limites-de-municipios-de-montevideo

## Qué se le hizo

1. **Reproyección.** El original está en UTM 21S (EPSG:32721), en metros.
   Se pasó a latitud/longitud (EPSG:4326), que es lo que usa Leaflet.
2. **Simplificación con topología compartida.** Un borde entre dos
   municipios vecinos se simplifica una sola vez, así no quedan huecos ni
   solapamientos entre ellos. De 111.038 puntos a 2.161.
3. **Redondeo** de coordenadas a 5 decimales (≈ 1 m).

Resultado: 46 KB en vez de 1,2 MB, con 99,94% de aciertos medido contra los
contornos sin simplificar sobre 20.000 puntos. El detalle está en el README
principal, sección "La zona se completa sola".

## Formato

```json
{ "Municipio A": [ [ [lat, lng], [lat, lng], ... ] ], ... }
```

Cada municipio tiene una lista de anillos, y cada anillo una lista de puntos
en `[lat, lng]` (el orden que espera Leaflet, al revés que el de GeoJSON).
