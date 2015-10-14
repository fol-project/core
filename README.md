[![Build Status](https://travis-ci.org/fol-project/core.svg?branch=master)](https://travis-ci.org/fol-project/core)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fol-project/core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fol-project/core/?branch=master)

Clases básicas que conforman o núcleo de FOL:

* `Fol` Xestiona a aplicación.
* `Fol\Container` Colector de dependencias moi básico, que soporta a interface [container-interop](https://github.com/container-interop/container-interop)
* `Fol\ServiceProvider` Para configurar servizos cargados por `Fol\Container`.
