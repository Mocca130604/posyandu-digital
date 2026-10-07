// Route helper client-side untuk Posyandu Digital
const routeMap = {
    'login': '/login',
    'login.store': '/login',
    'logout': '/logout',
    'dashboard': '/dashboard',
    'balita.index': '/balita',
    'balita.create': '/balita-baru/create',
    'balita.store': '/balita',
    'pengukuran.create': '/pengukuran/create',
    'pengukuran.preview': '/pengukuran/preview',
    'pengukuran.store': '/pengukuran',
    'imunisasi.index': '/imunisasi',
    'imunisasi.store': '/imunisasi',
    'orang-tua.dashboard': '/orang-tua/dashboard',
    'admin.posyandu.index': '/admin/posyandu',
    'admin.pengguna.index': '/admin/pengguna',
};

export function route(name, params) {
    if (!name) {
        return {
            current: (pattern) => {
                if (typeof window === 'undefined') return false;
                const path = window.location.pathname;
                if (!pattern) return path;

                if (pattern === 'dashboard') return path === '/dashboard';
                if (pattern === 'orang-tua.dashboard') return path === '/orang-tua/dashboard';
                if (pattern.startsWith('balita.')) return path.startsWith('/balita');
                if (pattern.startsWith('pengukuran.')) return path.startsWith('/pengukuran');
                if (pattern.startsWith('imunisasi.')) return path.startsWith('/imunisasi');
                if (pattern.startsWith('admin.posyandu.')) return path.startsWith('/admin/posyandu');
                if (pattern.startsWith('admin.pengguna.')) return path.startsWith('/admin/pengguna');

                const cleanPattern = pattern.replace(/\.\*/g, '').replace(/\./g, '/');
                return path.includes(cleanPattern);
            }
        };
    }

    if (name === 'balita.show') {
        const id = typeof params === 'object' && params !== null ? (params.balita || params.id || Object.values(params)[0]) : params;
        return `/balita/${id}`;
    }

    if (name === 'imunisasi.destroy') {
        const id = typeof params === 'object' && params !== null ? (params.imunisasi || params.id || Object.values(params)[0]) : params;
        return `/imunisasi/${id}`;
    }

    let url = routeMap[name] || `/${name.replace(/\./g, '/')}`;

    if (params && typeof params === 'object') {
        const queryParams = new URLSearchParams();
        for (const [key, val] of Object.entries(params)) {
            if (val !== undefined && val !== null) {
                queryParams.append(key, val);
            }
        }
        const queryString = queryParams.toString();
        if (queryString) {
            url += (url.includes('?') ? '&' : '?') + queryString;
        }
    }

    return url;
}

if (typeof window !== 'undefined') {
    window.route = route;
}
