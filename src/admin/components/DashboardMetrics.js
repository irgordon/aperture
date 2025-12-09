import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const DashboardMetrics = () => {
    const [metrics, setMetrics] = useState({ projects: 0, revenue: '0.00', outstanding: '0.00', conversion: 0 });
    useEffect(() => {
        Promise.all([apiFetch({ path: '/aperture/v1/leads' }), apiFetch({ path: '/aperture/v1/invoices' })]).then(([leads, invoices]) => {
            const revenue = invoices.reduce((acc, inv) => acc + (parseFloat(inv.amount_paid) || 0), 0);
            const outstanding = invoices.reduce((acc, inv) => acc + (parseFloat(inv.total_amount) - parseFloat(inv.amount_paid)), 0);
            const conversion = (leads.filter(l => l.stage === 'booked').length / leads.length) * 100 || 0;
            setMetrics({ projects: leads.length, revenue: revenue.toFixed(2), outstanding: outstanding.toFixed(2), conversion: conversion.toFixed(1) });
        });
    }, []);
    return (
        <div className="metrics-grid">
            <div className="metric-card"><div className="label">Projects</div><div className="value">{metrics.projects}</div></div>
            <div className="metric-card"><div className="label">Revenue</div><div className="value text-green">${metrics.revenue}</div></div>
            <div className="metric-card"><div className="label">Outstanding</div><div className="value text-orange">${metrics.outstanding}</div></div>
            <div className="metric-card"><div className="label">Conversion</div><div className="value">{metrics.conversion}%</div></div>
        </div>
    );
};
export default DashboardMetrics;
