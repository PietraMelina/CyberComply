export default function CmdStatusBadge() {
    const isMock = String(import.meta.env.VITE_CMD_MOCK_ENABLED || 'true') === 'true';

    if (!isMock) {
        return null;
    }

    return (
        <div className="fixed bottom-4 right-4 rounded border border-yellow-300 bg-yellow-100 px-3 py-2 text-xs text-yellow-900 shadow-lg z-50">
            <div className="font-semibold">Modo Teste CMD</div>
            <div>NIF simulado para demo.</div>
        </div>
    );
}

