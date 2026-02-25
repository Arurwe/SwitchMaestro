from sqlalchemy import create_engine, Column, Integer, String, Text, DateTime, ForeignKey, func,UniqueConstraint
from sqlalchemy.orm import relationship, declarative_base
from sqlalchemy.dialects.postgresql import JSONB

Base = declarative_base()

#  uwierzytelniania i definicji

class User(Base):
    __tablename__ = 'users'
    id = Column(Integer, primary_key=True, index=True)
    full_name = Column(String) 
    username = Column(String)
    email = Column(String, unique=True)
    task_logs = relationship("TaskLog", back_populates="user")
    commands = relationship("Command", back_populates="user")
    backups = relationship("ConfigurationBackup", back_populates="user")


class Vendor(Base):
    __tablename__ = 'vendors'
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String)
    netmiko_driver = Column(String, unique=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())
    
    devices = relationship("Device", back_populates="vendor")
    commands = relationship("Command", back_populates="vendor")


class Credential(Base):
    __tablename__ = 'credentials'
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String)
    username = Column(String)
    password = Column(Text) 
    secret = Column(Text, nullable=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())
    
    devices = relationship("Device", back_populates="credential")


# Akcje

class Action(Base):
    __tablename__ = 'actions'
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String)
    action_slug = Column(String, unique=True, index=True)
    description = Column(Text, nullable=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())
    commands = relationship("Command", back_populates="action")
    task_logs = relationship("TaskLog", back_populates="action_rel")



class Command(Base):
    __tablename__ = 'commands'
    id = Column(Integer, primary_key=True, index=True)
    vendor_id = Column(Integer, ForeignKey('vendors.id'))
    action_id = Column(Integer, ForeignKey('actions.id'))
    user_id = Column(Integer, ForeignKey('users.id'), default=1)
    commands = Column(JSONB, nullable=False)
    description = Column(Text, nullable=True)
    vendor = relationship("Vendor", back_populates="commands")
    action = relationship("Action", back_populates="commands")
    user = relationship("User", back_populates="commands")
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())


#  Urządzenia 

class Device(Base):
    __tablename__ = 'devices'
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String, index=True)
    ip_address = Column(String, unique=True, nullable=False)
    port = Column(Integer, default=22)
    description = Column(Text, nullable=True)
    status = Column(String, default='unknown')
    software_version = Column(Text, nullable=True)
    model = Column(Text, nullable=True)
    serial_number = Column(Text, nullable=True)
    uptime = Column(Text, nullable=True)
    vendor_id = Column(Integer, ForeignKey('vendors.id'))
    credential_id = Column(Integer, ForeignKey('credentials.id'))
    
    vendor = relationship("Vendor", back_populates="devices")
    credential = relationship("Credential", back_populates="devices")
    driver_override = Column(String, nullable=True)
    ports = relationship("DevicePort", back_populates="device")
    task_logs = relationship("TaskLog", back_populates="device")
    backups = relationship("ConfigurationBackup", back_populates="device")
    device_vlans = relationship("DeviceVlan", back_populates="device")
    
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())
    network_links_local = relationship("NetworkLink", foreign_keys='NetworkLink.local_device_id', back_populates="local_device")
    network_links_neighbor = relationship("NetworkLink", foreign_keys='NetworkLink.neighbor_device_id', back_populates="neighbor_device")



class DevicePort(Base):
    __tablename__ = 'device_ports'
    id = Column(Integer, primary_key=True, index=True)
    device_id = Column(Integer, ForeignKey('devices.id'))
    name = Column(String, index=True)
    status = Column(String, nullable=True, index=True)
    protocol_status = Column(String, nullable=True, index=True)
    description = Column(String, nullable=True)
    speed = Column(String, nullable=True)
    duplex = Column(String, nullable=True)
    details = Column(JSONB, nullable=True)
    
    device = relationship("Device", back_populates="ports")
    vlan_memberships = relationship(
        "PortVlanMembership", 
        back_populates="device_port", 
        cascade="all, delete-orphan"
    )
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())
    __table_args__ = (
        UniqueConstraint('device_id', 'name', name='uq_device_port_name'),
    )



class NetworkLink(Base):
    __tablename__ = 'network_links'
    
    id = Column(Integer, primary_key=True, index=True)
    local_device_id = Column(Integer, ForeignKey('devices.id', ondelete='CASCADE'), nullable=False)
    local_port_name = Column(String, nullable=False)
    neighbor_device_hostname = Column(String, nullable=False)
    neighbor_port_name = Column(String, nullable=False)
    neighbor_device_id = Column(Integer, ForeignKey('devices.id', ondelete='SET NULL'), nullable=True)
    discovered_at = Column(DateTime, nullable=False)  
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())
    
    __table_args__ = (
        UniqueConstraint('local_device_id', 'local_port_name', name='uq_local_device_port'),
    )
    
    local_device = relationship("Device", foreign_keys=[local_device_id], back_populates="network_links_local")
    neighbor_device = relationship("Device", foreign_keys=[neighbor_device_id], back_populates="network_links_neighbor")


class Vlan(Base):
    __tablename__ = 'vlans'
    id = Column(Integer, primary_key=True, index=True)
    vlan_id = Column(Integer, unique=True, nullable=False, index=True)  
    name = Column(String, nullable=True)
    description = Column(Text, nullable=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    device_vlans = relationship("DeviceVlan", back_populates="vlan")
    port_memberships = relationship("PortVlanMembership", back_populates="vlan")


class DeviceVlan(Base):

    __tablename__ = 'device_vlan' 
    id = Column(Integer, primary_key=True)
    device_id = Column(Integer, ForeignKey('devices.id', ondelete='CASCADE'), nullable=False)
    type = Column(String, nullable=True)
    route_interface = Column(String, nullable=True)
    vlan_id = Column(Integer, ForeignKey('vlans.id', ondelete='CASCADE'), nullable=False)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    device = relationship("Device", back_populates="device_vlans")
    vlan = relationship("Vlan", back_populates="device_vlans")

    __table_args__ = (
        UniqueConstraint('device_id', 'vlan_id', name='uq_device_vlan_link'),
    )

class PortVlanMembership(Base):
    """
    Tabela pivot (wiele-do-wielu) łącząca porty (DevicePort) 
    z globalnymi definicjami VLAN-ów (Vlan).
    """
    __tablename__ = 'port_vlan_membership'
    
    id = Column(Integer, primary_key=True)
    device_port_id = Column(Integer, ForeignKey('device_ports.id', ondelete='CASCADE'), nullable=False)
    vlan_id = Column(Integer, ForeignKey('vlans.id', ondelete='CASCADE'), nullable=False)
    membership_type = Column(String, nullable=True) 
    
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

    device_port = relationship("DevicePort", back_populates="vlan_memberships")
    vlan = relationship("Vlan", back_populates="port_memberships")

    __table_args__ = (
        UniqueConstraint('device_port_id', 'vlan_id', name='uq_port_vlan_membership'),
    )

# logi

class ConfigurationBackup(Base):
    __tablename__ = 'configuration_backups'
    id = Column(Integer, primary_key=True, index=True)
    device_id = Column(Integer, ForeignKey('devices.id'))
    user_id = Column(Integer, ForeignKey('users.id'), nullable=True)
    configuration = Column(Text)
    
    device = relationship("Device", back_populates="backups")
    user = relationship("User", back_populates="backups")
    
    created_at = Column(DateTime, server_default=func.now(), index=True)
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now(), index=True)


class TaskLog(Base):
    __tablename__ = 'task_logs'
    id = Column(Integer, primary_key=True, index=True)
    job_id = Column(String, nullable=True, index=True) 
    batch_id = Column(String, nullable=True, index=True)
    user_id = Column(Integer, ForeignKey('users.id'), nullable=True)
    device_id = Column(Integer, ForeignKey('devices.id'), nullable=True)
    action_id = Column(Integer, ForeignKey('actions.id'), nullable=True) 
    
    action = Column(String)
    status = Column(String)
    intention_prompt = Column(Text, nullable=True)
    command_sent = Column(Text, nullable=True)
    raw_output = Column(Text, nullable=True)
    error_message = Column(Text, nullable=True)
    system_info = Column(Text, nullable=True)
    device = relationship("Device", back_populates="task_logs")
    user = relationship("User", back_populates="task_logs") 

    action_rel = relationship("Action", foreign_keys=[action_id], back_populates="task_logs")
    
    created_at = Column(DateTime, server_default=func.now(), index=True)
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now(), index=True)