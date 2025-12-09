import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const ProjectDetailModal = ({ project, onClose, onUpdate }) => {
    const [activeTab, setActiveTab] = useState('overview');
    const [tasks, setTasks] = useState([]);
    const [activity, setActivity] = useState([]);

    useEffect(() => {
        if(project.id) {
            apiFetch({ path: `/aperture/v1/projects/${project.id}/details` }).then(res => {
                setTasks(res.tasks);
                setActivity(res.activity);
            });
        }
    }, [project]);

    const addTask = async (e) => {
        if(e.key === 'Enter') {
            const text = e.target.value;
            const newTask = await apiFetch({ path: '/aperture/v1/tasks', method: 'POST', data: { lead_id: project.id, description: text } });
            setTasks([...tasks, newTask]);
            e.target.value = '';
        }
    };

    return (
        <div className="ap-modal-overlay">
            <div className="ap-modal full-height">
                <header className="modal-header">
                    <div>
                        <h2>{project.title}</h2>
                        <span className="client-badge">{project.client_name}</span>
                    </div>
                    <button onClick={onClose}>×</button>
                </header>
                
                <div className="project-layout">
                    <aside className="project-sidebar">
                        <nav>
                            <button className={activeTab==='overview'?'active':''} onClick={()=>setActiveTab('overview')}>Overview</button>
                            <button className={activeTab==='documents'?'active':''} onClick={()=>setActiveTab('documents')}>Documents</button>
                            <button className={activeTab==='email'?'active':''} onClick={()=>setActiveTab('email')}>Communication</button>
                        </nav>
                        
                        <div className="checklist-widget">
                            <h4>To-Do List</h4>
                            <input placeholder="+ Add task (Enter)" onKeyDown={addTask} />
                            <ul>
                                {tasks.map(t => (
                                    <li key={t.id}>
                                        <input type="checkbox" checked={t.is_completed} onChange={()=>{/* Toggle Logic */}} />
                                        <span>{t.description}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </aside>

                    <main className="project-content">
                        {activeTab === 'overview' && (
                            <div className="overview-grid">
                                <div className="stat-card">
                                    <label>Status</label>
                                    <select value={project.stage} onChange={()=>{/* Update Logic */}}>
                                        <option value="inquiry">Inquiry</option>
                                        <option value="booked">Booked</option>
                                    </select>
                                </div>
                                <div className="stat-card">
                                    <label>Project Value</label>
                                    <div className="big-num">${project.value}</div>
                                </div>
                                <div className="activity-feed">
                                    <h3>Recent Activity</h3>
                                    {activity.map(a => (
                                        <div key={a.id} className="activity-item">
                                            <span className={`icon ${a.type}`}>•</span>
                                            <p>{a.message}</p>
                                            <small>{new Date(a.created_at).toLocaleDateString()}</small>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                        
                        {activeTab === 'documents' && (
                            <div className="docs-list">
                                <div className="doc-card">
                                    <span className="icon">📄</span>
                                    <div>
                                        <h4>Contract</h4>
                                        <span className="status signed">Signed</span>
                                    </div>
                                    <button>View</button>
                                </div>
                                <div className="doc-card">
                                    <span className="icon">💳</span>
                                    <div>
                                        <h4>Invoice #1001</h4>
                                        <span className="status unpaid">Unpaid</span>
                                    </div>
                                    <button>Send Reminder</button>
                                </div>
                            </div>
                        )}
                    </main>
                </div>
            </div>
        </div>
    );
};
export default ProjectDetailModal;
