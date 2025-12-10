import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import DashboardMetrics from './DashboardMetrics';
import ProjectDetailModal from './ProjectDetailModal';

const LeadList = () => {
    const [leads, setLeads] = useState([]);
    const [selectedProject, setSelectedProject] = useState(null);

    const stages = [
        { id: 'inquiry', label: 'Inquiry' },
        { id: 'proposal', label: 'Proposal' },
        { id: 'contract', label: 'Contract' },
        { id: 'booked', label: 'Booked' },
        { id: 'completed', label: 'Completed' }
    ];

    useEffect(() => {
        fetchLeads();
    }, []);

    const fetchLeads = () => {
        apiFetch({ path: '/aperture/v1/leads' }).then(setLeads);
    };

    const getLeadsByStage = (stage) => leads.filter(l => l.stage === stage);

    const moveStage = async (id, newStage) => {
        // Optimistic UI Update
        const updatedLeads = leads.map(l => l.id === id ? { ...l, stage: newStage } : l);
        setLeads(updatedLeads);
        
        await apiFetch({ 
            path: `/aperture/v1/leads/${id}`, 
            method: 'POST', 
            data: { stage: newStage } 
        });
    };

    return (
        <div className="ap-pipeline">
            <DashboardMetrics />
            
            <header className="ap-header">
                <h2>Projects & Leads</h2>
                <div style={{display:'flex', gap:'10px'}}>
                    <button className="btn-secondary">Filter</button>
                    <button className="btn-primary" onClick={() => setSelectedProject({})}>+ New Project</button>
                </div>
            </header>

            <div className="pipeline-board">
                {stages.map(stage => (
                    <div key={stage.id} className="pipeline-column">
                        <div className="column-header">
                            <span>{stage.label}</span>
                            <span className="count">{getLeadsByStage(stage.id).length}</span>
                        </div>
                        <div className="column-body">
                            {getLeadsByStage(stage.id).map(lead => (
                                <div key={lead.id} className="project-card" onClick={() => setSelectedProject(lead)}>
                                    <div className="card-tags">
                                        <span className="tag new">Active</span>
                                    </div>
                                    <strong>{lead.title || 'Untitled Project'}</strong>
                                    <div className="client">
                                        <span className="dashicons dashicons-businesswoman"></span> 
                                        {lead.first_name ? `${lead.first_name} ${lead.last_name}` : 'Unknown Client'}
                                    </div>
                                    <div className="card-meta">
                                        <span>${lead.project_value || '0.00'}</span>
                                        <span>{lead.event_date ? new Date(lead.event_date).toLocaleDateString() : 'No Date'}</span>
                                        {/* Quick Move Button */}
                                        {stage.id !== 'completed' && (
                                            <button 
                                                className="btn-secondary" 
                                                style={{padding: '2px 6px', fontSize: '10px'}}
                                                onClick={(e) => { 
                                                    e.stopPropagation(); 
                                                    const nextIdx = stages.findIndex(s => s.id === stage.id) + 1;
                                                    if(stages[nextIdx]) moveStage(lead.id, stages[nextIdx].id);
                                                }}
                                            >
                                                →
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>

            {selectedProject && (
                <ProjectDetailModal 
                    project={selectedProject} 
                    onClose={() => setSelectedProject(null)} 
                    onUpdate={fetchLeads}
                />
            )}
        </div>
    );
};
export default LeadList;
