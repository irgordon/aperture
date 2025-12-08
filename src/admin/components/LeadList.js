import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import CreateInvoiceModal from './CreateInvoiceModal';

const LeadList = () => {
    const [leads, setLeads] = useState([]);
    const [modalOpen, setModalOpen] = useState(false);

    useEffect(() => {
        apiFetch({path: '/aperture/v1/leads'}).then(setLeads);
    }, []);

    return (
        <div className="ap-leads">
            <div className="header">
                <h2>Pipeline</h2>
                <button className="btn-primary" onClick={() => setModalOpen(true)}>Create Invoice</button>
            </div>
            <table>
                <thead><tr><th>Client</th><th>Status</th><th>Value</th></tr></thead>
                <tbody>
                    {leads.map(l => (
                        <tr key={l.id}>
                            <td>{l.first_name} {l.last_name}</td>
                            <td><span className={`badge ${l.status}`}>{l.status}</span></td>
                            <td>${l.project_value}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <CreateInvoiceModal isOpen={modalOpen} onClose={()=>setModalOpen(false)} leads={leads} />
        </div>
    );
};
export default LeadList;
