import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import ProjectDetailModal from './ProjectDetailModal';

const PipelineBoard = () => {
    const [leads, setLeads] = useState([]);
    const [selectedProject, setSelectedProject] = useState(null);

    const stages = [
        { id: 'inquiry', label: 'Inquiry' },
        { id: 'proposal', label: 'Proposal Sent' },
        { id: 'contract', label: 'Contract Sent' },
        { id: 'booked', label: 'Booked' },
        { id: 'completed', label: 'Completed' }
    ];

    useEffect(() => {
        apiFetch({ path: '/aperture/v1/leads' }).then(setLeads);
    }, []);

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
            <header className="pipeline-header">
                <h2>Project Pipeline</h2>
                <button className="btn-primary" onClick={() => setSelectedProject('new')}>+ New Project</button>
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
                                        <span className="value">${lead.value}</span>
                                    </div>
                                    <div className="client-name">{lead.client_name}</div>
                                    <div className="card-footer">
                                        <span className="date">{lead.event_date || 'No Date'}</span>
                                        {/* Example: Move buttons for simplicity, Drag-n-Drop can be added later */}
                                        <div className="actions">
                                            {stage.id !== 'completed' && <button onClick={(e) => { e.stopPropagation(); moveStage(lead.id, stages[stages.indexOf(stage)+1].id); }}>→</button>}
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
                    onUpdate={() => apiFetch({ path: '/aperture/v1/leads' }).then(setLeads)}
                />
            )}
        </div>
    );
};
export default PipelineBoard;
