import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const CreateInvoiceModal = ({ isOpen, onClose, leads }) => {
    const [data, setData] = useState({ leadId: '', amount: '', dueDate: '', title: '' });
    const [submitting, setSubmitting] = useState(false);

    if (!isOpen) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await apiFetch({ path: '/aperture/v1/invoices', method: 'POST', data });
            alert('Invoice Created!');
            onClose();
        } catch (err) { alert(err.message); }
        setSubmitting(false);
    };

    return (
        <div className="ap-modal-overlay">
            <div className="ap-modal">
                <h3>New Invoice</h3>
                <form onSubmit={handleSubmit}>
                    <select required onChange={e => setData({...data, leadId: e.target.value})}>
                        <option value="">Select Lead</option>
                        {leads.map(l => <option key={l.id} value={l.id}>{l.first_name} {l.last_name}</option>)}
                    </select>
                    <input type="text" placeholder="Description" required onChange={e => setData({...data, title: e.target.value})} />
                    <input type="number" placeholder="Amount" required onChange={e => setData({...data, amount: e.target.value})} />
                    <input type="date" required onChange={e => setData({...data, dueDate: e.target.value})} />
                    <button className="btn-primary" disabled={submitting}>Create</button>
                    <button type="button" className="btn-secondary" onClick={onClose} style={{marginLeft:'10px'}}>Cancel</button>
                </form>
            </div>
        </div>
    );
};
export default CreateInvoiceModal;
