import React from 'react';
import ReactDOM from 'react-dom';
import { setConfig } from 'react-hot-loader';
import InitialLoader from '@arelix/themes/arelix/components/elements/InitialLoader';

import './i18n';

setConfig({ reloadHooks: false });

const theme = (window as any).SiteConfiguration?.theme || 'default';

(async () => {
    const appElement = document.getElementById('app');
    if (appElement) {
        ReactDOM.render(React.createElement(InitialLoader), appElement);
    }

    if (theme === 'arelix') {
        const initializeArelix = await import('@arelix/themes/arelix/index');
        await initializeArelix.default();
    } else {
        const AppImport = await import('@/components/App');
        const App = AppImport.default;
        ReactDOM.render(<App />, document.getElementById('app'));
    }
})();
