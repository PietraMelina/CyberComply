export function useMfa() {
    const api = window.axios;

    const status = async () => {
        const { data } = await api.get('/api/mfa/status');
        return data;
    };

    const setup = async () => {
        const { data } = await api.post('/api/mfa/setup');
        return data;
    };

    const confirm = async (code) => {
        const { data } = await api.post('/api/mfa/confirm', { code });
        return data;
    };

    const disable = async (code) => {
        const { data } = await api.post('/api/mfa/disable', { code });
        return data;
    };

    const sendEmailToken = async (tempToken) => {
        const { data } = await api.post('/api/mfa/email', { temp_token: tempToken });
        return data;
    };

    const verifyEmailToken = async (tempToken, code) => {
        const { data } = await api.post('/api/mfa/verify-email', {
            temp_token: tempToken,
            code,
        });
        return data;
    };

    const verify = async (tempToken, code, type = 'totp') => {
        const { data } = await api.post('/api/mfa/verify', {
            temp_token: tempToken,
            code,
            type,
        });
        return data;
    };

    return {
        status,
        setup,
        confirm,
        disable,
        sendEmailToken,
        verifyEmailToken,
        verify,
    };
}

