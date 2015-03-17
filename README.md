[![Build Status](https://travis-ci.org/fol-project/core.svg?branch=master)](https://travis-ci.org/fol-project/core)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fol-project/core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fol-project/core/?branch=master)

Clases básicas que conforman o núcleo de FOL:

* `Fol\Fol` Xestiona variables e servizos globais
* `Fol\App` Xestiona a aplicación web.
* `Fol\Bag` Clase xenérica para gardar valores
* `Fol\Config` Xestiona os valores de configuración
* `Fol\Container` Colector de dependencias moi básico, que soporta a interface [container-interop](https://github.com/container-interop/container-interop)
* `Fol\ServiceProvider` Para configurar servizos cargados por `Fol\Container`.
