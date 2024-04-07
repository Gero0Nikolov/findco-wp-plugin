window.addEventListener('load', (event) => {

    window.andromedaAutoloadDependencies = [];

    window.breakpoints = {
        sm: 576,
        md: 768,
        lg: 992,
    };
    
    window.autoload = [
        {
            script: 'vote',
            className: 'Vote',
            onload: () => {
                window.Vote.init();
            },
            loadWhen: () => {
                return (
                    typeof document.querySelector('.findco-voting-box') !== 'undefined' &&
                    document.querySelector('.findco-voting-box') !== null
                );
            }
        },
    ];

    window.scriptsDir = fcrPublicConfig.scripts.dir;

    autoload.forEach((element) => {
        
        if (
            typeof element.loadWhen === 'function' &&
            element.loadWhen() 
        ) { 
            if (
                typeof element.worksWith !== 'undefined' &&
                element.worksWith !== null &&
                element.worksWith.length > 0
            ) {

                window.andromedaAutoloadDependencies.push({
                    element: element,
                    loaded: 0,
                });

                element.worksWith.forEach((dependency) => {
                    window.loadDependencyScript(dependency.script, (window.andromedaAutoloadDependencies.length - 1));
                });
            } else { 
                window.loadScript(element);
            }
        }
    });
});

window.loadScript = (element) => {
    const script = document.createElement('script');
    script.src = `${fcrPublicConfig.scripts.dir}${element.script}.js?ver=${fcrPublicConfig.scripts.version}`;
    script.async = 'async';
    script.onload = () => {

        if (
            element.loadWhen() &&
            typeof element.onload !== 'undefined' &&
            typeof window[element.className] === 'object'
        ) {

            element.onload();
        }
    };

    // Append the Script
    document.body.appendChild(script);
}

window.loadDependencyScript = (dependency, id) => {
    const script = document.createElement('script');
    script.src = `${fcrPublicConfig.scripts.dependenciesDir}${dependency}.js?ver=${fcrPublicConfig.scripts.version}`;
    script.async = 'async';
    script.onload = () => {
        window.andromedaAutoloadDependencies[id].loaded += 1;
        if (window.andromedaAutoloadDependencies[id].loaded >= window.andromedaAutoloadDependencies[id].element.worksWith.length) {
            window.loadScript(window.andromedaAutoloadDependencies[id].element);
        }
    };

    // Append the Script
    document.body.appendChild(script);
}