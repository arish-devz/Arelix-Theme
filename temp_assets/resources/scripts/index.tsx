import React from 'react';
import ReactDOM from 'react-dom';
import { setConfig } from 'react-hot-loader';
import InitialLoader from '@rolexdev/themes/hyperv1/components/elements/InitialLoader';

import './i18n';

setConfig({ reloadHooks: false });

const theme = (window as any).SiteConfiguration?.theme || 'default';

(async () => {
    const appElement = document.getElementById('app');
    if (appElement) {
        ReactDOM.render(React.createElement(InitialLoader), appElement);
    }

    if (theme === 'hyperv1') {
        const initializeHyperV1 = await import('@rolexdev/themes/hyperv1/index');
        await initializeHyperV1.default();
    } else {
        const AppImport = await import('@/components/App');
        const App = AppImport.default;
        ReactDOM.render(<App />, document.getElementById('app'));
    }
})();
