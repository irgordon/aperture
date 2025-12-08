import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const TemplateEditor = () => {
    const [templates, setTemplates] = useState([]);
    useEffect(() => { apiFetch({path: '/aperture/v1/templates'}).then(setTemplates); }, []);

    const update = (id, field, val) => setTemplates(templates.map(t => t.id === id ? { ...t, [field]: val } : t));
    const save = async (t) => {
        await apiFetch({ path: '/aperture/v1/templates', method: 'POST', data: t });
        alert('Saved!');
    };

    return (
        <div style={{display:'flex', flexDirection:'column', gap:'20px'}}>
            {templates.map(t => (
                <div key={t.id} className="ap-card">
                    <h3>{t.name}</h3>
                    <input style={{width:'100%', marginBottom:'10px'}} value={t.subject} onChange={e => update(t.id, 'subject', e.target.value)} />
                    <textarea rows="5" style={{width:'100%', marginBottom:'10px'}} value={t.body} onChange={e => update(t.id, 'body', e.target.value)} />
                    <button className="btn-secondary" onClick={() => save(t)}>Save Template</button>
                </div>
            ))}
        </div>
    );
};
export default TemplateEditor;
