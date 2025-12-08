import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import CreateInvoiceModal from './CreateInvoiceModal';

const LeadList = () => {
    const [leads, setLeads] = useState([]);
    const [modalOpen, setModalOpen] = useState(false);

    useEffect(() => { apiFetch({path: '/aperture/v1/leads'}).then(setLeads); }, []);

    const sendEmail = async (lead, slug) => {
        if(!confirm(`Send "${slug}" email to ${lead.first_name}?`)) return;
        try {
            await apiFetch({
                path: '/aperture/v1/email/send', method: 'POST',
                data: { slug, email: lead.email, client_name: lead.first_name }
            });
            alert('Email Sent!');
        } catch(e) { alert('Error sending email'); }
    };

    return (
        <div className="ap-leads">
            <div className="header">
                <h2>Pipeline</h2>
                <button className="btn-primary" onClick={() => setModalOpen(true)}>+ Create Invoice</button>
            </div>
            <div className="table-container">
                <table>
                    <thead><tr><th>Client</th><th>Status</th><th>Value</th><th>Actions</th></tr></thead>
                    <tbody>
                        {leads.map(l => (
                            <tr key={l.id}>
                                <td>{l.first_name} {l.last_name}</td>
                                <td><span className={`badge ${l.status ? l.status.toLowerCase() : 'new'}`}>{l.status}</span></td>
                                <td>${l.project_value}</td>
                                <td>
                                    <select onChange={(e) => { if(e.target.value) sendEmail(l, e.target.value); e.target.value=''; }}>
                                        <option value="">Send Email...</option>
                                        <option value="invoice_reminder">Invoice Reminder</option>
                                        <option value="photos_ready">Photos Ready</option>
                                        <option value="booking_confirmed">Date Confirmation</option>
                                    </select>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <CreateInvoiceModal isOpen={modalOpen} onClose={()=>setModalOpen(false)} leads={leads} />
        </div>
    );
};
export default LeadList;
