import { useState, useRef } from 'react';
import SignatureCanvas from 'react-signature-canvas';
import apiFetch from '@wordpress/api-fetch';

const ContractManager = ({ contract }) => {
    const sigPad = useRef({});
    
    const signAsAdmin = async () => {
        if (sigPad.current.isEmpty()) return alert('Please sign.');
        const signature = sigPad.current.getTrimmedCanvas().toDataURL('image/png');
        await apiFetch({ path: '/aperture/v1/contracts/admin-sign', method: 'POST', data: { id: contract.id, signature } });
        alert('Countersigned! Final PDF generated.');
        window.location.reload();
    };

    return (
        <div className="ap-contract-viewer">
            <div className="contract-content" dangerouslySetInnerHTML={{__html: contract.content}} />
            <div className="signatures">
                <div className="client-sig"><h4>Client Signature</h4>{contract.client_signature ? <img src={contract.client_signature} /> : <span className="badge pending">Pending Client</span>}</div>
                <div className="admin-sig"><h4>Photographer Signature</h4>{contract.admin_signature ? (<img src={contract.admin_signature} />) : (contract.client_signature ? (<div className="sign-box"><SignatureCanvas penColor="black" canvasProps={{width: 300, height: 150, className: 'sigCanvas'}} ref={sigPad} /><button className="btn-primary" onClick={signAsAdmin}>Countersign</button></div>) : (<p>Waiting for client...</p>))}</div>
            </div>
        </div>
    );
};
export default ContractManager;
