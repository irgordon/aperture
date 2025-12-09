import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import ProjectDetailModal from './ProjectDetailModal';

const LeadList = () => {
    const [leads, setLeads] = useState([]);
    const [selectedProject, setSelectedProject] = useState(null);

    // Define the stages for the Kanban board
    const stages = [
        { id: 'inquiry', label: 'Inquiry' },
        { id: 'proposal', label: 'Proposal' },
        { id: 'contract', label: 'Contract' },
        { id: 'booked', label: 'Booked' },
        { id: 'completed', label: 'Completed' }
    ];

    useEffect(() => {
        apiFetch({ path: '/aperture/v1/leads' }).then(setLeads);
    }, []);

    const getLeadsByStage = (stage) => leads.filter(l => l.stage === stage);

    const moveStage = async (id, newStage) => {
        // Optimistic UI Update: update state immediately before API returns
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
            <header className="pipeline-header">
                <h2>Project Pipeline</h2>
                {/* Placeholder for new project creation logic */}
                <button className="btn-primary" onClick={() => alert('Create New Lead logic here')}>+ New Project</button>
            </header>

            <div className="pipeline-board">
                {stages.map(stage => (
                    <div key={stage.id} className="pipeline-column">
                        <div className="column-header">
                            <span className="stage-name">{stage.label}</span>
                            <span className="count">{getLeadsByStage(stage.id).length}</span>
                        </div>
                        <div className="column-body">
                            {getLeadsByStage(stage.id).map(lead => (
                                <div key={lead.id} className="project-card" onClick={() => setSelectedProject(lead)}>
                                    <div className="card-top">
                                        <strong>{lead.title || 'Untitled Project'}</strong>
                                        {/* Display value if it exists */}
                                        <span className="value">${lead.project_value || '0.00'}</span>
                                    </div>
                                    {/* Display client name if joined, otherwise ID */}
                                    <div className="client-name">{lead.first_name ? `${lead.first_name} ${lead.last_name}` : `Contact #${lead.contact_id}`}</div>
                                    <div className="card-footer">
                                        <span className="date">{lead.event_date || 'No Date'}</span>
                                        <div className="actions">
                                            {/* Simple 'Next Stage' button */}
                                            {stage.id !== 'completed' && (
                                                <button 
                                                    className="btn-icon"
                                                    title="Move to next stage"
                                                    onClick={(e) => { 
                                                        e.stopPropagation(); 
                                                        const nextStageIndex = stages.findIndex(s => s.id === stage.id) + 1;
                                                        if (stages[nextStageIndex]) {
                                                            moveStage(lead.id, stages[nextStageIndex].id); 
                                                        }
                                                    }}
                                                >
                                                    →
                                                </button>
                                            )}
                                        </div>
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
                    // Refresh data when modal closes or updates
                    onUpdate={() => apiFetch({ path: '/aperture/v1/leads' }).then(setLeads)}
                />
            )}
        </div>
    );
};
export default LeadList;
