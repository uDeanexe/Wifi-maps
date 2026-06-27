const cableEnhancementStyle = document.createElement('style');
cableEnhancementStyle.textContent = `
    [data-map-panel] {
        right: 4rem !important;
        width: min(19rem, calc(100% - 5rem)) !important;
    }

    #network-map .leaflet-top.leaflet-right {
        top: 96px !important;
        right: 8px !important;
    }

    .cable-save-dialog {
        width: min(420px, calc(100vw - 2rem));
        border: 0;
        border-radius: 16px;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .28);
    }

    .cable-save-dialog::backdrop {
        background: rgba(15, 23, 42, .42);
    }

    .cable-save-card {
        background: #fff;
        padding: 1rem;
    }

    .cable-save-field {
        display: grid;
        gap: .5rem;
    }

    .cable-save-label {
        font-size: .75rem;
        font-weight: 800;
        color: #475569;
    }

    .cable-save-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: .75rem;
        padding: .65rem .8rem;
        font-size: .875rem;
        outline: none;
    }

    .cable-save-input:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, .18);
    }

    @media (max-width: 640px) {
        [data-map-panel] {
            left: .75rem !important;
            right: .75rem !important;
            top: 3.7rem !important;
            width: auto !important;
        }

        #network-map .leaflet-top.leaflet-right {
            top: 160px !important;
        }
    }
`;
document.head.appendChild(cableEnhancementStyle);

function ensureCableSaveDialog() {
    let dialog = document.querySelector('[data-cable-save-dialog]');
    if (dialog) return dialog;

    dialog = document.createElement('dialog');
    dialog.className = 'cable-save-dialog';
    dialog.dataset.cableSaveDialog = '1';
    dialog.innerHTML = `
        <form method="dialog" class="cable-save-card" data-cable-save-form>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;border-bottom:1px solid #f1f5f9;padding-bottom:.75rem">
                <div>
                    <div style="font-size:.95rem;font-weight:900;color:#0f172a">Simpan Kabel</div>
                    <div style="margin-top:.25rem;font-size:.75rem;color:#64748b">Isi nama kabel dan warna sebelum garis disimpan.</div>
                </div>
                <button type="button" data-cable-save-close style="border:1px solid #e2e8f0;border-radius:.55rem;background:white;padding:.35rem .55rem;font-size:.75rem;font-weight:800;color:#475569">Tutup</button>
            </div>
            <div style="display:grid;gap:.8rem;margin-top:1rem">
                <label class="cable-save-field">
                    <span class="cable-save-label">Nama kabel</span>
                    <input class="cable-save-input" data-cable-name placeholder="Contoh: Kabel FO Jambu 12 - Jambu 20" autocomplete="off">
                </label>
                <label class="cable-save-field">
                    <span class="cable-save-label">Warna kabel</span>
                    <div style="display:flex;align-items:center;gap:.75rem;border:1px solid #e2e8f0;background:#f8fafc;border-radius:.75rem;padding:.55rem .75rem">
                        <input type="color" data-cable-color value="#0284c7" style="height:2.3rem;width:3.5rem;cursor:pointer;border:1px solid #cbd5e1;border-radius:.45rem;background:white">
                        <span data-cable-color-label style="font-size:.75rem;font-weight:800;color:#64748b">#0284c7</span>
                    </div>
                </label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid #f1f5f9;margin-top:1rem;padding-top:1rem">
                <button type="button" data-cable-save-cancel style="border:1px solid #e2e8f0;border-radius:.65rem;background:white;padding:.55rem .8rem;font-size:.875rem;font-weight:800;color:#475569">Batal</button>
                <button type="submit" style="border:0;border-radius:.65rem;background:#0284c7;padding:.55rem .8rem;font-size:.875rem;font-weight:900;color:white">Simpan kabel</button>
            </div>
        </form>
    `;
    document.body.appendChild(dialog);
    return dialog;
}

function askCableDetails(payload) {
    const dialog = ensureCableSaveDialog();
    const form = dialog.querySelector('[data-cable-save-form]');
    const nameInput = dialog.querySelector('[data-cable-name]');
    const colorInput = dialog.querySelector('[data-cable-color]');
    const colorLabel = dialog.querySelector('[data-cable-color-label]');
    const closeButton = dialog.querySelector('[data-cable-save-close]');
    const cancelButton = dialog.querySelector('[data-cable-save-cancel]');
    const properties = payload?.properties || {};
    const sourceCode = properties.source_node_code || 'NODE';
    const targetCode = properties.target_node_code || 'NODE';
    const panelColor = document.querySelector('[data-line-color]')?.value;
    const defaultColor = properties.color || panelColor || '#0284c7';

    nameInput.value = properties.cable_name || payload.name || `${sourceCode} -> ${targetCode}`;
    colorInput.value = defaultColor;
    colorLabel.textContent = defaultColor;

    const syncColor = () => { colorLabel.textContent = colorInput.value; };
    colorInput.addEventListener('input', syncColor);

    return new Promise((resolve, reject) => {
        const cleanup = () => {
            form.removeEventListener('submit', onSubmit);
            closeButton.removeEventListener('click', onCancel);
            cancelButton.removeEventListener('click', onCancel);
            dialog.removeEventListener('cancel', onCancel);
            colorInput.removeEventListener('input', syncColor);
        };
        const onCancel = (event) => {
            event?.preventDefault?.();
            cleanup();
            dialog.close();
            reject(new Error('Penyimpanan kabel dibatalkan.'));
        };
        const onSubmit = (event) => {
            event.preventDefault();
            const cableName = nameInput.value.trim() || `${sourceCode} -> ${targetCode}`;
            const color = colorInput.value || defaultColor;
            cleanup();
            dialog.close();
            resolve({ cableName, color });
        };

        form.addEventListener('submit', onSubmit);
        closeButton.addEventListener('click', onCancel);
        cancelButton.addEventListener('click', onCancel);
        dialog.addEventListener('cancel', onCancel);
        dialog.showModal();
        nameInput.focus();
        nameInput.select();
    });
}

const originalFetch = window.fetch.bind(window);
window.fetch = async (input, init = {}) => {
    const url = typeof input === 'string' ? input : input?.url;
    const method = (init?.method || 'GET').toUpperCase();

    if (url && method === 'POST' && /\/map\/drawings(?:$|\?|\/)/.test(url) && typeof init.body === 'string') {
        try {
            const payload = JSON.parse(init.body);
            const props = payload?.properties || {};
            const isCableRoute = payload?.type === 'polyline'
                && props.source_node_id
                && props.target_node_id
                && !props.cable_name;

            if (isCableRoute) {
                const { cableName, color } = await askCableDetails(payload);
                payload.name = cableName;
                payload.properties = {
                    ...props,
                    cable_name: cableName,
                    color,
                };
                init = {
                    ...init,
                    body: JSON.stringify(payload),
                };
            }
        } catch (error) {
            if (error?.message === 'Penyimpanan kabel dibatalkan.') {
                throw error;
            }
        }
    }

    return originalFetch(input, init);
};
