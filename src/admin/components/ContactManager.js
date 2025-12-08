import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const ContactManager = () => {
    const [contacts, setContacts] = useState([]);
    const [isEditing, setIsEditing] = useState(false);
    const [form, setForm] = useState({ id: '', first_name: '', last_name: '', email: '', phone: '', address: '' });

    useEffect(() => {
        loadContacts();
    }, []);

    const loadContacts = () => {
        apiFetch({ path: '/aperture/v1/contacts' }).then(setContacts);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        await apiFetch({ path: '/aperture/v1/contacts', method: 'POST', data: form });
        setIsEditing(false);
        setForm({ id: '', first_name: '', last_name: '', email: '', phone: '', address: '' });
        loadContacts();
    };

    const editContact = (contact) => {
        setForm(contact);
        setIsEditing(true);
    };

    return (
        <div className="ap-contacts">
            <div className="header" style={{display:'flex', justifyContent:'space-between', marginBottom:'20px'}}>
                <h2>Customer Database</h2>
                <button className="btn-primary" onClick={() => setIsEditing(true)}>+ Add Customer</button>
            </div>

            {isEditing && (
                <div className="ap-card" style={{marginBottom:'30px'}}>
                    <h3>{form.id ? 'Edit Customer' : 'New Customer'}</h3>
                    <form onSubmit={handleSubmit} style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:'15px'}}>
                        <input placeholder="First Name" value={form.first_name} onChange={e => setForm({...form, first_name: e.target.value})} required />
                        <input placeholder="Last Name" value={form.last_name} onChange={e => setForm({...form, last_name: e.target.value})} required />
                        <input placeholder="Email" type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} required />
                        <input placeholder="Phone" value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} />
                        <textarea placeholder="Address" value={form.address} onChange={e => setForm({...form, address: e.target.value})} style={{gridColumn:'span 2', width:'100%', padding:'10px', border:'1px solid #ccc', borderRadius:'4px'}} />
                        <div style={{gridColumn:'span 2', display:'flex', gap:'10px'}}>
                            <button className="btn-primary">Save Customer</button>
                            <button type="button" className="btn-secondary" onClick={() => setIsEditing(false)}>Cancel</button>
                        </div>
                    </form>
                </div>
            )}

            <div className="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {contacts.map(c => (
                            <tr key={c.id}>
                                <td><strong>{c.first_name} {c.last_name}</strong></td>
                                <td><a href={`mailto:${c.email}`}>{c.email}</a></td>
                                <td>{c.phone}</td>
                                <td>{c.address}</td>
                                <td>
                                    <button className="btn-secondary small" onClick={() => editContact(c)}>Edit</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};
export default ContactManager;
