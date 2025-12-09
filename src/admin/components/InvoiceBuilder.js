import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const InvoiceBuilder = ({ leadId, onClose }) => {
    const [invoice, setInvoice] = useState({ lead_id: leadId, items: [{ description: 'Service', price: 0, qty: 1 }], settings: { tax_rate: 0, service_fee: 0 }, due_date: '' });
    const updateItem = (index, field, value) => { const newItems = [...invoice.items]; newItems[index][field] = value; setInvoice({ ...invoice, items: newItems }); };
    const addItem = () => setInvoice({ ...invoice, items: [...invoice.items, { description: '', price: 0, qty: 1 }] });
    const calculateTotal = () => { const subtotal = invoice.items.reduce((sum, item) => sum + (item.price * item.qty), 0); return (subtotal * (1 + invoice.settings.tax_rate/100) + parseFloat(invoice.settings.service_fee)).toFixed(2); };
    const save = async () => { await apiFetch({ path: '/aperture/v1/invoices', method: 'POST', data: { ...invoice, total_amount: calculateTotal() } }); alert('Saved'); onClose(); };

    return (
        <div className="ap-modal-overlay"><div className="ap-modal large">
            <h3>Invoice Builder</h3>
            {invoice.items.map((item, i) => (
                <div key={i} style={{display:'flex', gap:'10px'}}><input value={item.description} onChange={e=>updateItem(i,'description',e.target.value)} /><input type="number" value={item.price} onChange={e=>updateItem(i,'price',e.target.value)} /><input type="number" value={item.qty} onChange={e=>updateItem(i,'qty',e.target.value)} /></div>
            ))}
            <button className="btn-secondary" onClick={addItem}>+ Item</button>
            <div style={{marginTop:'20px'}}>Total: ${calculateTotal()}</div>
            <button className="btn-primary" onClick={save}>Create</button>
        </div></div>
    );
};
export default InvoiceBuilder;
